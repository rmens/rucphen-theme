/**
 * Radio Rucphen frontend JS.
 *
 * - Sticky audio player met Media Session, volume in localStorage.
 * - zwfm-metadata WebSocket connectie met exponential backoff reconnect.
 * - Schedule day-tabs.
 * - Search overlay (debounced REST fetch).
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

	let currentMeta = null;
	let lastMessageAt = 0;

	function setMediaSession(title, artist) {
		if (!('mediaSession' in navigator)) return;
		try {
			navigator.mediaSession.metadata = new MediaMetadata({
				title: title || station.name,
				artist: artist || station.tagline,
				album: station.name,
			});
			navigator.mediaSession.setActionHandler('play', () => audio.play());
			navigator.mediaSession.setActionHandler('pause', () => audio.pause());
		} catch (_) { /* ignore */ }
	}

	function applyMeta(meta) {
		currentMeta = meta;
		lastMessageAt = Date.now();

		const title = meta.title || meta.formatted_metadata || station.name;
		const artist = meta.artist || '';

		const playerTitle = document.querySelector('[data-player-title]');
		const playerArtist = document.querySelector('[data-player-artist]');
		const heroNow = document.querySelector('[data-hero-now]');

		if (playerTitle) playerTitle.textContent = title;
		if (playerArtist) playerArtist.textContent = artist;
		if (heroNow) heroNow.textContent = artist ? title + ' - ' + artist : title;

		setMediaSession(title, artist);
	}

	function showFallback() {
		const playerTitle = document.querySelector('[data-player-title]');
		const playerArtist = document.querySelector('[data-player-artist]');
		const heroNow = document.querySelector('[data-hero-now]');
		if (playerTitle) playerTitle.textContent = station.name;
		if (playerArtist) playerArtist.textContent = station.tagline || '';
		if (heroNow) heroNow.textContent = station.tagline || '';
	}

	function connectWebSocket() {
		const url = stream.metadataWebsocketUrl;
		if (!url) {
			showFallback();
			return;
		}

		let attempts = 0;
		const minDelay = (stream.metadataReconnectMinSeconds || 2) * 1000;
		const maxDelay = (stream.metadataReconnectMaxSeconds || 30) * 1000;

		function open() {
			let ws;
			try {
				ws = new WebSocket(url);
			} catch (e) {
				schedule();
				return;
			}

			ws.addEventListener('open', () => { attempts = 0; });
			ws.addEventListener('message', (evt) => {
				try {
					const payload = JSON.parse(evt.data);
					if (payload && typeof payload === 'object') {
						applyMeta(payload);
					}
				} catch (_) { /* invalid payload, ignore */ }
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
			if (Date.now() - lastMessageAt > staleAfter) {
				showFallback();
			}
		}, 5000);
	}

	function bindPlayer() {
		const player = document.querySelector('[data-component="sticky-player"]');
		const toggle = document.querySelector('[data-player-toggle]');
		const volume = document.querySelector('[data-player-volume]');
		const heroPlay = document.querySelector('[data-hero-play]');

		if (player) player.hidden = false;

		function play() {
			audio.play().then(() => {
				if (player) player.classList.add('is-playing');
			}).catch(() => { /* autoplay blocked */ });
		}

		function pause() {
			audio.pause();
			if (player) player.classList.remove('is-playing');
		}

		if (toggle) {
			toggle.addEventListener('click', () => {
				if (audio.paused) play(); else pause();
			});
		}
		if (heroPlay) {
			heroPlay.addEventListener('click', play);
		}
		if (volume) {
			volume.value = String(Math.round(audio.volume * 100));
			volume.addEventListener('input', () => {
				const v = Math.max(0, Math.min(1, Number(volume.value) / 100));
				audio.volume = v;
				localStorage.setItem('rucphen.volume', String(v));
			});
		}
	}

	function bindSchedule() {
		const root = document.querySelector('[data-component="schedule"]');
		if (!root) return;
		root.querySelectorAll('[data-day]').forEach((btn) => {
			btn.addEventListener('click', () => {
				const day = btn.getAttribute('data-day');
				root.querySelectorAll('[data-day]').forEach((b) => b.setAttribute('aria-selected', b === btn ? 'true' : 'false'));
				root.querySelectorAll('[data-day-panel]').forEach((panel) => {
					panel.hidden = panel.getAttribute('data-day-panel') !== day;
				});
			});
		});
	}

	function bindSearch() {
		const overlay = document.getElementById('rucphen-search-overlay');
		if (!overlay) return;

		const input = overlay.querySelector('[data-search-input]');
		const results = overlay.querySelector('[data-search-results]');
		const closeBtn = overlay.querySelector('[data-search-close]');

		document.querySelectorAll('[data-search-open]').forEach((btn) => {
			btn.addEventListener('click', () => {
				if (typeof overlay.showModal === 'function') overlay.showModal();
				else overlay.setAttribute('open', '');
				if (input) input.focus();
			});
		});

		if (closeBtn) {
			closeBtn.addEventListener('click', () => {
				if (typeof overlay.close === 'function') overlay.close();
				else overlay.removeAttribute('open');
			});
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
							'<a href="' + item.url + '" class="rucphen-search-result">' +
								'<strong>' + escapeHtml(item.title) + '</strong>' +
								(item.excerpt ? '<p>' + escapeHtml(item.excerpt) + '</p>' : '') +
							'</a>'
						)).join('');
					} catch (_) { results.innerHTML = '<p>Zoeken mislukte.</p>'; }
				}, 250);
			});
		}
	}

	function escapeHtml(s) {
		return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
	}

	function init() {
		bindPlayer();
		bindSchedule();
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
