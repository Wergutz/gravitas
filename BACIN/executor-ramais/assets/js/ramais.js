/* App do Executor de Ramais — apoio ao formulário:
   1) GPS e horário do aparelho junto com o ramal;
   2) prévia da foto escolhida;
   3) compressão no próprio celular antes de enviar (economiza dados de campo);
   4) trava o botão para não salvar o mesmo ramal duas vezes.
   Tudo é opcional: sem JavaScript o formulário continua enviando normalmente. */
(function () {
  'use strict';

  var TIPOS = ['ramal', 'lancamento_via', 'acabado'];
  var LADO_MAX = 1600, QUALIDADE = 0.82;

  // ── GPS + horário ──────────────────────────────────────────
  var status = document.getElementById('gps-status');
  var lat = document.getElementById('lat'), lng = document.getElementById('lng'), ts = document.getElementById('ts');
  if (ts) ts.value = new Date().toISOString();

  if (navigator.geolocation && status) {
    navigator.geolocation.watchPosition(function (p) {
      if (lat) lat.value = p.coords.latitude.toFixed(7);
      if (lng) lng.value = p.coords.longitude.toFixed(7);
      status.textContent = '📍 GPS ok (±' + Math.round(p.coords.accuracy) + ' m)';
    }, function () {
      status.textContent = '📍 GPS desligado';
    }, { enableHighAccuracy: true, maximumAge: 15000, timeout: 10000 });
  } else if (status) {
    status.textContent = '📍 sem GPS';
  }

  // ── Prévia + compressão ────────────────────────────────────
  function comprimir(file) {
    return new Promise(function (resolve) {
      if (!file || !/^image\//.test(file.type) || !window.createImageBitmap) return resolve(file);
      createImageBitmap(file).then(function (bmp) {
        var escala = Math.min(1, LADO_MAX / Math.max(bmp.width, bmp.height));
        if (escala === 1 && file.size < 1.5 * 1024 * 1024) return resolve(file);
        var c = document.createElement('canvas');
        c.width = Math.round(bmp.width * escala);
        c.height = Math.round(bmp.height * escala);
        c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
        c.toBlob(function (blob) {
          resolve(blob ? new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' }) : file);
        }, 'image/jpeg', QUALIDADE);
      }).catch(function () { resolve(file); });
    });
  }

  TIPOS.forEach(function (tipo) {
    var input  = document.getElementById('foto_' + tipo);
    var previa = document.getElementById('previa_' + tipo);
    if (!input) return;

    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file) { if (previa) previa.style.display = 'none'; return; }

      if (previa) {
        previa.src = URL.createObjectURL(file);
        previa.style.display = 'block';
      }

      comprimir(file).then(function (menor) {
        if (menor === file || !window.DataTransfer) return;
        var dt = new DataTransfer();
        dt.items.add(menor);
        input.files = dt.files;
      });
    });
  });

  // ── Evita envio duplicado ──────────────────────────────────
  var form = document.getElementById('form-ramal');
  if (form) {
    form.addEventListener('submit', function () {
      var btn = document.getElementById('btn-salvar');
      if (btn) {
        setTimeout(function () {
          btn.disabled = true;
          btn.textContent = 'Enviando…';
        }, 0);
      }
    });
  }
})();
