/**
 * Radio Rucphen frontend JS.
 *
 * - Sticky audio player met Media Session, volume in localStorage.
 * - zwfm-metadata WebSocket connectie met exponential backoff reconnect.
 * - Program guide day-tabs and podcast filters.
 * - Search overlay (debounced REST fetch).
 * - Mobile menu toggle.
 */

(function () {
	const boot = window.RucphenBoot || {};
	const stream = boot.stream || {};
	const station = boot.station || { name: 'Radio Rucphen', tagline: '' };

	const audio = new Audio();
	audio.preload = 'none';
	audio.src = stream.url || '';
	audio.crossOrigin = 'anonymous';

	const storedVolume = parseFloat(localStorage.getItem('rucphen.volume'));
	audio.volume = Number.isFinite(storedVolume) ? Math.max(0, Math.min(1, storedVolume)) : 0.8;

	let lastMessageAt = 0;
	let artworkToken = 0;
	let defaultArtworkUrl = '';
	const artworkCache = new Map();

	function defaultCoverUrl() {
		if (defaultArtworkUrl) return defaultArtworkUrl;
		const cover = document.querySelector('[data-player-cover], [data-hero-cover]');
		defaultArtworkUrl = cover ? cover.getAttribute('src') : '';
		return defaultArtworkUrl;
	}

	function setCover(url, title, artist) {
		const fallback = defaultCoverUrl();
		const coverUrl = url || fallback;
		const alt = url ? (artist ? title + ' - ' + artist : title) : '';

		document.querySelectorAll('[data-player-cover], [data-hero-cover]').forEach((el) => {
			if (coverUrl) el.setAttribute('src', coverUrl);
			el.setAttribute('alt', alt);
		});
	}

	function setMediaSession(title, artist, artworkUrl) {
		if (!('mediaSession' in navigator)) return;
		try {
			const metadata = {
				title: title || station.name,
				artist: artist || station.tagline,
				album: station.name,
			};
			if (artworkUrl) {
				metadata.artwork = [
					{ src: artworkUrl, sizes: '600x600' },
				];
			}
			navigator.mediaSession.metadata = new MediaMetadata(metadata);
			navigator.mediaSession.setActionHandler('play', () => audio.play());
			navigator.mediaSession.setActionHandler('pause', () => audio.pause());
		} catch (_) { /* ignore */ }
	}

	function lookupArtwork(meta, title, artist) {
		if (!stream.coverLookupEnabled) {
			setCover('', title, artist);
			setMediaSession(title, artist);
			return;
		}

		const key = [artist || '', title || '', meta.formatted_metadata || ''].join('\u0001');
		const cached = artworkCache.get(key);
		const token = ++artworkToken;

		if (cached !== undefined) {
			const artworkUrl = cached && cached.found ? cached.artworkUrl : '';
			setCover(artworkUrl, title, artist);
			setMediaSession(title, artist, artworkUrl);
			return;
		}

		const restRoot = boot.restRoot || '/wp-json/radio-rucphen/v1/';
		const params = new URLSearchParams({
			title: title || '',
			artist: artist || '',
			formatted: meta.formatted_metadata || '',
		});

		fetch(restRoot + 'now-playing-artwork?' + params.toString(), { credentials: 'same-origin' })
			.then((res) => (res.ok ? res.json() : null))
			.then((data) => {
				if (token !== artworkToken) return;
				artworkCache.set(key, data || { found: false });
				const artworkUrl = data && data.found ? data.artworkUrl : '';
				setCover(artworkUrl, title, artist);
				setMediaSession(title, artist, artworkUrl);
			})
			.catch(() => {
				if (token !== artworkToken) return;
				artworkCache.set(key, { found: false });
				setCover('', title, artist);
				setMediaSession(title, artist);
			});
	}

	function applyMeta(meta) {
		lastMessageAt = Date.now();
		const title = meta.title || meta.formatted_metadata || station.name;
		const artist = meta.artist || '';

		document.querySelectorAll('[data-player-title]').forEach((el) => { el.textContent = title; });
		document.querySelectorAll('[data-player-artist]').forEach((el) => { el.textContent = artist; });
		document.querySelectorAll('[data-hero-title]').forEach((el) => { el.textContent = title; });
		document.querySelectorAll('[data-hero-artist]').forEach((el) => { el.textContent = artist; });
		document.querySelectorAll('[data-live-now]').forEach((el) => {
			el.textContent = artist ? title + ' - ' + artist : title;
		});

		lookupArtwork(meta, title, artist);
	}

	function showFallback() {
		document.querySelectorAll('[data-player-title]').forEach((el) => { el.textContent = station.name + ' - Live'; });
		document.querySelectorAll('[data-player-artist]').forEach((el) => { el.textContent = station.tagline || ''; });
		document.querySelectorAll('[data-hero-artist]').forEach((el) => { el.textContent = station.tagline || ''; });
		artworkToken++;
		setCover('', station.name + ' - Live', station.tagline || '');
		setMediaSession(station.name + ' - Live', station.tagline || '');
	}

	function connectWebSocket() {
		const url = stream.metadataWebsocketUrl;
		if (!url) { showFallback(); return; }

		let attempts = 0;
		const minDelay = (stream.metadataReconnectMinSeconds || 2) * 1000;
		const maxDelay = (stream.metadataReconnectMaxSeconds || 30) * 1000;

		function open() {
			let ws;
			try { ws = new WebSocket(url); } catch (e) { schedule(); return; }
			ws.addEventListener('open', () => { attempts = 0; });
			ws.addEventListener('message', (evt) => {
				try {
					const payload = JSON.parse(evt.data);
					if (payload && typeof payload === 'object') applyMeta(payload);
				} catch (_) {}
			});
			ws.addEventListener('close', schedule);
			ws.addEventListener('error', () => { try { ws.close(); } catch (_) {} });
		}

		function schedule() {
			attempts++;
			const delay = Math.min(maxDelay, minDelay * Math.pow(2, attempts - 1));
			setTimeout(open, delay);
		}

		open();
	}

	function staleWatcher() {
		const staleAfter = (stream.metadataStaleAfterSeconds || 60) * 1000;
		setInterval(() => {
			if (!lastMessageAt) return;
			if (Date.now() - lastMessageAt > staleAfter) showFallback();
		}, 5000);
	}

	function bindPlayer() {
		const player = document.querySelector('[data-component="sticky-player"]');
		const toggles = document.querySelectorAll('[data-player-toggle], [data-hero-play]');
		const volume = document.querySelector('[data-player-volume]');
		const playIcon = document.querySelector('[data-player-icon-play]');
		const pauseIcon = document.querySelector('[data-player-icon-pause]');

		function setPlaying(on) {
			if (player) player.classList.toggle('is-playing', on);
			if (playIcon) playIcon.hidden = on;
			if (pauseIcon) pauseIcon.hidden = !on;
		}

		function play() { audio.play().then(() => setPlaying(true)).catch(() => {}); }
		function pause() { audio.pause(); setPlaying(false); }

		toggles.forEach((btn) => {
			btn.addEventListener('click', () => {
				if (btn.dataset.heroPlay !== undefined) { play(); return; }
				if (audio.paused) play(); else pause();
			});
		});

		if (volume) {
			volume.value = String(Math.round(audio.volume * 100));
			volume.addEventListener('input', () => {
				const v = Math.max(0, Math.min(1, Number(volume.value) / 100));
				audio.volume = v;
				localStorage.setItem('rucphen.volume', String(v));
			});
		}

		document.querySelectorAll('[data-podcast-audio]').forEach((podcastAudio) => {
			podcastAudio.addEventListener('play', () => {
				audio.pause();
				setPlaying(false);
			});
		});
	}

	function bindMobileMenu() {
		const toggle = document.querySelector('[data-mobile-toggle]');
		const panel = document.querySelector('[data-mobile-panel]');
		if (!toggle || !panel) return;
		toggle.addEventListener('click', () => {
			const open = panel.hidden;
			panel.hidden = !open;
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	}

	function bindProgramGuide() {
		const root = document.querySelector('[data-component="program-guide"]');
		if (!root) return;
		const dayIndex = {
			monday: 1,
			tuesday: 2,
			wednesday: 3,
			thursday: 4,
			friday: 5,
			saturday: 6,
			sunday: 7
		};
		const toMinutes = (time) => {
			const match = /^(\d{1,2}):([0-5]\d)$/.exec(String(time || '').trim());
			if (!match) return -1;
			return Number(match[1]) * 60 + Number(match[2]);
		};
		const isLiveRow = (row, now = new Date()) => {
			const rowDay = dayIndex[row.getAttribute('data-day') || ''];
			const start = toMinutes(row.getAttribute('data-from'));
			const end = toMinutes(row.getAttribute('data-to'));
			if (!rowDay || start < 0 || end < 0) return false;

			const today = now.getDay() === 0 ? 7 : now.getDay();
			const minutes = now.getHours() * 60 + now.getMinutes();
			if (end <= start) {
				const nextDay = rowDay === 7 ? 1 : rowDay + 1;
				return (today === rowDay && minutes >= start) || (today === nextDay && minutes < end);
			}
			return today === rowDay && minutes >= start && minutes < end;
		};
		const updateLiveBadges = () => {
			root.querySelectorAll('[data-guide-row]').forEach((row) => {
				const badge = row.querySelector('[data-live-badge]');
				if (badge) badge.classList.toggle('hidden', !isLiveRow(row));
			});
		};

		root.querySelectorAll('[data-day]').forEach((btn) => {
			btn.addEventListener('click', () => {
				const day = btn.getAttribute('data-day');
				root.querySelectorAll('[data-day]').forEach((b) => {
					const active = b === btn ? 'true' : 'false';
					b.setAttribute('aria-selected', active);
					b.setAttribute('aria-pressed', active);
				});
				root.querySelectorAll('[data-day-panel]').forEach((panel) => {
					panel.hidden = panel.getAttribute('data-day-panel') !== day;
				});
			});
		});

		updateLiveBadges();
		window.setInterval(updateLiveBadges, 60 * 1000);
	}

	function bindPodcastArchive() {
		const root = document.querySelector('[data-component="podcast-archive"]');
		if (!root) return;

		root.querySelectorAll('[data-podcast-filter]').forEach((btn) => {
			btn.addEventListener('click', () => {
				const selected = btn.getAttribute('data-podcast-filter') || 'all';
				root.querySelectorAll('[data-podcast-filter]').forEach((filter) => {
					filter.setAttribute('aria-pressed', filter === btn ? 'true' : 'false');
				});
				root.querySelectorAll('[data-podcast-card]').forEach((card) => {
					const program = card.getAttribute('data-podcast-program') || '';
					card.hidden = selected !== 'all' && program !== selected;
				});
			});
		});
	}

	function bindNewsArchive() {
		const root = document.querySelector('[data-component="news-archive"]');
		if (!root) return;

		root.querySelectorAll('[data-news-filter]').forEach((btn) => {
			btn.addEventListener('click', () => {
				const selected = btn.getAttribute('data-news-filter') || 'all';
				root.querySelectorAll('[data-news-filter]').forEach((filter) => {
					filter.setAttribute('aria-pressed', filter === btn ? 'true' : 'false');
				});
				root.querySelectorAll('[data-news-card]').forEach((card) => {
					const source = card.getAttribute('data-news-source') || '';
					card.hidden = selected !== 'all' && source !== selected;
				});
			});
		});
	}

	function bindZuidwestModal() {
		const dialog = document.getElementById('rucphen-zwu-modal');
		if (!dialog) return;

		const frame = dialog.querySelector('[data-zwu-modal-frame]');
		const status = dialog.querySelector('[data-zwu-modal-status]');
		const closeBtn = dialog.querySelector('[data-zwu-modal-close]');
		if (!frame || !status) return;

		function showDialog() {
			if (typeof dialog.showModal === 'function') {
				if (!dialog.open) dialog.showModal();
			} else {
				dialog.setAttribute('open', '');
			}
		}

		function closeDialog() {
			if (typeof dialog.close === 'function') dialog.close();
			else dialog.removeAttribute('open');
			frame.srcdoc = '';
		}

		function fallbackDoc(docTitle, message, sourceUrl) {
			return '<!doctype html><html lang="nl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
				'<style>body{margin:0;padding:28px;font-family:Inter,Arial,sans-serif;color:#0f172a;line-height:1.55}a{color:#003576;font-weight:800}</style>' +
				'<title>' + escapeHtml(docTitle) + '</title></head><body><h1>' + escapeHtml(docTitle) + '</h1><p>' + escapeHtml(message) + '</p>' +
				(sourceUrl ? '<p><a href="' + escapeHtml(sourceUrl) + '" target="_blank" rel="noopener nofollow">Open het artikel op Zuidwest Update</a></p>' : '') +
				'</body></html>';
		}

		function cardTitle(link) {
			const cardHeading = link.querySelector('strong');
			const text = cardHeading ? cardHeading.textContent.trim() : '';
			return text || 'Nieuwsbericht';
		}

		function sendZuidwestPageview(sourceUrl) {
			if (!sourceUrl || !window.fetch) return;
			const payload = {
				n: 'pageview',
				v: 3,
				u: sourceUrl,
				d: 'zuidwestupdate.nl',
				r: window.location.href,
			};

			fetch('https://stats.zuidwesttv.nl/api/event', {
				method: 'POST',
				headers: { 'Content-Type': 'text/plain' },
				keepalive: true,
				body: JSON.stringify(payload),
			}).catch(() => {});
		}

		async function openArticle(link) {
			const id = link.getAttribute('data-zwu-item') || '';
			if (!id) return;

			const sourceUrl = link.href;
			const initialTitle = cardTitle(link);
			status.hidden = false;
			status.textContent = 'Artikel laden bij Zuidwest Update...';
			frame.srcdoc = fallbackDoc(initialTitle, 'Artikel laden...', '');
			showDialog();
			sendZuidwestPageview(sourceUrl);

			try {
				const restRoot = boot.restRoot || '/wp-json/radio-rucphen/v1/';
				const url = restRoot + 'zuidwest-article?id=' + encodeURIComponent(id) + '&_=' + Date.now();
				const res = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
				const data = await res.json();
				if (!res.ok) {
					throw new Error(data && data.message ? data.message : 'Laden mislukt.');
				}

				frame.srcdoc = data.html || fallbackDoc(data.title || initialTitle, 'Geen artikelinhoud gevonden.', sourceUrl);
				status.hidden = true;
			} catch (err) {
				const message = err && err.message ? err.message : 'Artikel laden mislukt.';
				status.hidden = false;
				status.textContent = message;
				frame.srcdoc = fallbackDoc(initialTitle, message, sourceUrl);
			}
		}

		document.addEventListener('click', (evt) => {
			if (evt.defaultPrevented || evt.button !== 0 || evt.metaKey || evt.ctrlKey || evt.shiftKey || evt.altKey) return;
			const link = evt.target.closest('[data-zwu-modal-link]');
			if (!link) return;
			evt.preventDefault();
			openArticle(link);
		});

		if (closeBtn) closeBtn.addEventListener('click', closeDialog);
		dialog.addEventListener('click', (evt) => {
			if (evt.target === dialog) closeDialog();
		});
		dialog.addEventListener('close', () => {
			frame.srcdoc = '';
		});
	}

	function bindEventsArchive() {
		const root = document.querySelector('[data-component="events-archive"]');
		if (!root) return;

		root.querySelectorAll('[data-event-filter]').forEach((btn) => {
			btn.addEventListener('click', () => {
				const selected = btn.getAttribute('data-event-filter') || 'all';
				root.querySelectorAll('[data-event-filter]').forEach((filter) => {
					filter.setAttribute('aria-pressed', filter === btn ? 'true' : 'false');
				});
				root.querySelectorAll('[data-event-card]').forEach((card) => {
					const month = card.getAttribute('data-event-month') || '';
					card.hidden = selected !== 'all' && month !== selected;
				});
			});
		});
	}

	function bindSearch() {
		const overlay = document.getElementById('rucphen-search-overlay');
		const input = overlay && overlay.querySelector('[data-search-input]');
		const results = overlay && overlay.querySelector('[data-search-results]');

		document.querySelectorAll('[data-search-open]').forEach((btn) => {
			btn.addEventListener('click', () => {
				if (!overlay) return;
				if (typeof overlay.showModal === 'function') overlay.showModal();
				else overlay.setAttribute('open', '');
				if (input) input.focus();
			});
		});

		if (overlay) {
			const closeBtn = overlay.querySelector('[data-search-close]');
			if (closeBtn) {
				closeBtn.addEventListener('click', () => {
					if (typeof overlay.close === 'function') overlay.close();
					else overlay.removeAttribute('open');
				});
			}
		}

		let timer;
		if (input && results) {
			input.addEventListener('input', () => {
				clearTimeout(timer);
				const q = input.value.trim();
				if (q.length < 2) { results.innerHTML = ''; return; }
				timer = setTimeout(async () => {
					try {
						const url = (boot.restRoot || '/wp-json/radio-rucphen/v1/') + 'search?q=' + encodeURIComponent(q);
						const res = await fetch(url, { credentials: 'same-origin' });
						const data = await res.json();
						results.innerHTML = (data.results || []).map((item) => (
							'<a href="' + item.url + '" class="search-result">' +
								'<strong>' + escapeHtml(item.title) + '</strong>' +
								'<span class="meta">' + escapeHtml(item.type) + '</span>' +
								(item.excerpt ? '<span>' + escapeHtml(item.excerpt) + '</span>' : '') +
							'</a>'
						)).join('');
					} catch (_) { results.innerHTML = '<p class="meta">Zoeken mislukte.</p>'; }
				}, 250);
			});
		}
	}

	function escapeHtml(s) {
		return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
	}

	function init() {
		bindPlayer();
		bindMobileMenu();
		bindProgramGuide();
		bindPodcastArchive();
		bindNewsArchive();
		bindZuidwestModal();
		bindEventsArchive();
		bindSearch();
		connectWebSocket();
		staleWatcher();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
