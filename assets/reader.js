(function () {
  'use strict';
  const formatTime = (seconds) => {
    if (!Number.isFinite(seconds) || seconds < 0) return '00:00';
    const m = Math.floor(seconds / 60), s = Math.floor(seconds % 60);
    return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
  };
  function initPlayer(wrapper, audioUrl) {
    const audio = wrapper.querySelector('audio');
    const button = wrapper.querySelector('.elwr-generate');
    const icon = wrapper.querySelector('.elwr-play-icon');
    const status = wrapper.querySelector('.elwr-status');
    const controls = wrapper.querySelector('.elwr-controls');
    const seek = wrapper.querySelector('.elwr-seek');
    const current = wrapper.querySelector('.elwr-current');
    const duration = wrapper.querySelector('.elwr-duration');
    const speed = wrapper.querySelector('.elwr-speed');
    // The browser receives a URL to a server-generated MP3 only. No text or API key is sent to JavaScript.
    audio.src = audioUrl;
    audio.preload = 'none';
    controls.hidden = false;
    audio.addEventListener('loadedmetadata', () => { duration.textContent = formatTime(audio.duration); });
    audio.addEventListener('timeupdate', () => { current.textContent = formatTime(audio.currentTime); seek.value = audio.duration ? String(audio.currentTime / audio.duration * 100) : '0'; });
    seek.addEventListener('input', () => { if (audio.duration) audio.currentTime = Number(seek.value) / 100 * audio.duration; });
    if (speed) speed.addEventListener('change', () => { audio.playbackRate = Number(speed.value); });
    wrapper.querySelectorAll('.elwr-skip').forEach((skip) => skip.addEventListener('click', () => {
      if (Number.isFinite(audio.duration)) audio.currentTime = Math.max(0, Math.min(audio.duration, audio.currentTime + Number(skip.dataset.seconds || 0)));
    }));
    audio.addEventListener('play', () => { icon.textContent = 'Ⅱ'; if (status && !status.hidden) status.textContent = 'Playing audio'; });
    audio.addEventListener('pause', () => { icon.textContent = '▶'; if (!audio.ended && status && !status.hidden) status.textContent = 'Audio paused'; });
    audio.addEventListener('ended', () => { icon.textContent = '▶'; if (status && !status.hidden) status.textContent = 'Finished listening'; });
    button.dataset.ready = '1';
    button.addEventListener('click', () => {
      if (audio.paused) audio.play().catch(() => { if (status && !status.hidden) status.textContent = 'Audio is ready. Press play again to start.'; });
      else audio.pause();
    });
  }
  document.addEventListener('click', async function (event) {
    const button = event.target.closest('.elwr-generate');
    if (!button || button.dataset.ready === '1') return;
    const wrapper = button.closest('.elwr-reader');
    if (!wrapper) return;
    const status = wrapper.querySelector('.elwr-status');
    const config = window.ELWR || {};
    const endpoint = wrapper.dataset.api || (config.endpoint || '').replace(/\/$/, '') + '/' + wrapper.dataset.postId;
    button.disabled = true;
    if (status && !status.hidden) status.textContent = 'Loading cached MP3…';
    try {
      const url = new URL(endpoint, window.location.href);
      url.searchParams.set('mode', wrapper.dataset.mode || 'post');
      const response = await fetch(url.toString(), { method: 'GET', headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
      const data = await response.json();
      if (!response.ok || !data.ready || !data.audio_url) throw new Error(data.message || config.errorText || 'Cached audio is not ready yet.');
      initPlayer(wrapper, data.audio_url);
      const audio = wrapper.querySelector('audio');
      await audio.play().catch(() => {});
    } catch (error) {
      if (status && !status.hidden) status.textContent = error.message || config.errorText || 'Audio is not ready yet.';
      else button.setAttribute('aria-label', error.message || 'Audio not ready; contact the site administrator');
    } finally { button.disabled = false; }
  });
})();
