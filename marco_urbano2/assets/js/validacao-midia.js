/* ---------------------------------------------------------------------------
 *  CONFERÊNCIA NO NAVEGADOR — antes de enviar
 * ---------------------------------------------------------------------------
 *  Lê o EXIF do arquivo no próprio celular/computador e avisa na hora se a
 *  foto não serve. Evita a pessoa esperar o upload de 12 MB para descobrir
 *  que foi recusada.
 *
 *  Isto é conveniência, NÃO é segurança: a validação que vale é a do servidor
 *  (12-validacao-upload.php). Nunca confiar só nesta.
 *
 *  Uso no medicao.php:
 *    <script src="/marco_urbano2/assets/validacao-midia.js"></script>
 *    <script>MUValidacao.ligar(document.querySelector('input[name="fotos[]"]'));</script>
 * ------------------------------------------------------------------------ */

(function (global) {
  'use strict';

  var CFG = {
    obraLat: -30.2072,        // Barra do Quaraí/RS  >>> CONFIRMAR por contrato
    obraLon: -57.5547,
    raioKm: 15,
    megapixelsMin: 6.0,
    diasMaxAtras: 120
  };

  /* ---------- leitura mínima de EXIF direto do arquivo ------------------ */

  function lerExif(buffer) {
    var v = new DataView(buffer);
    if (v.byteLength < 4 || v.getUint16(0) !== 0xFFD8) return null;   // não é JPEG

    var off = 2, app1 = -1, temJFIF = false;
    while (off < v.byteLength - 4) {
      if (v.getUint8(off) !== 0xFF) break;
      var marcador = v.getUint8(off + 1);
      var tam = v.getUint16(off + 2);
      if (marcador === 0xE0) temJFIF = true;
      if (marcador === 0xE1) { app1 = off + 4; break; }
      if (marcador === 0xDA) break;
      off += 2 + tam;
    }
    if (app1 < 0) return { temExif: false, temJFIF: temJFIF };

    // "Exif\0\0"
    if (v.getUint32(app1) !== 0x45786966) return { temExif: false, temJFIF: temJFIF };
    var tiff = app1 + 6;
    var le = v.getUint16(tiff) === 0x4949;
    var ifd0 = tiff + v.getUint32(tiff + 4, le);
    var n = v.getUint16(ifd0, le);
    if (n === 0) return { temExif: false, temJFIF: temJFIF };   // contêiner vazio

    var out = { temExif: true, temJFIF: temJFIF, data: null, lat: null, lon: null, aparelho: null };
    var exifIFD = null, gpsIFD = null, make = null, model = null;

    function texto(entrada) {
      var cnt = v.getUint32(entrada + 4, le);
      var pos = cnt > 4 ? tiff + v.getUint32(entrada + 8, le) : entrada + 8;
      var s = '';
      for (var i = 0; i < cnt - 1 && pos + i < v.byteLength; i++) s += String.fromCharCode(v.getUint8(pos + i));
      return s.trim();
    }
    function racionais(entrada, qtd) {
      var pos = tiff + v.getUint32(entrada + 8, le), r = [];
      for (var i = 0; i < qtd; i++) {
        var num = v.getUint32(pos + i * 8, le), den = v.getUint32(pos + i * 8 + 4, le);
        r.push(den ? num / den : 0);
      }
      return r;
    }

    for (var i = 0; i < n; i++) {
      var e = ifd0 + 2 + i * 12, tag = v.getUint16(e, le);
      if (tag === 0x8769) exifIFD = tiff + v.getUint32(e + 8, le);
      else if (tag === 0x8825) gpsIFD = tiff + v.getUint32(e + 8, le);
      else if (tag === 0x010F) make = texto(e);
      else if (tag === 0x0110) model = texto(e);
    }
    if (make || model) out.aparelho = ((make || '') + ' ' + (model || '')).trim();

    if (exifIFD) {
      var ne = v.getUint16(exifIFD, le);
      for (var j = 0; j < ne; j++) {
        var ee = exifIFD + 2 + j * 12;
        if (v.getUint16(ee, le) === 0x9003) {          // DateTimeOriginal
          var s = texto(ee);                            // "2026:07:31 14:22:05"
          var m = s.match(/^(\d{4}):(\d{2}):(\d{2}) (\d{2}):(\d{2}):(\d{2})$/);
          if (m) out.data = new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +m[6]);
        }
      }
    }

    if (gpsIFD) {
      var ng = v.getUint16(gpsIFD, le), latR = null, lonR = null, lat = null, lon = null;
      for (var k = 0; k < ng; k++) {
        var ge = gpsIFD + 2 + k * 12, gt = v.getUint16(ge, le);
        if (gt === 0x0001) latR = texto(ge);
        else if (gt === 0x0002) lat = racionais(ge, 3);
        else if (gt === 0x0003) lonR = texto(ge);
        else if (gt === 0x0004) lon = racionais(ge, 3);
      }
      if (lat) { out.lat = (lat[0] + lat[1] / 60 + lat[2] / 3600) * (latR === 'S' ? -1 : 1); }
      if (lon) { out.lon = (lon[0] + lon[1] / 60 + lon[2] / 3600) * (lonR === 'W' ? -1 : 1); }
    }

    return out;
  }

  function distanciaKm(a1, o1, a2, o2) {
    var R = 6371, dA = (a2 - a1) * Math.PI / 180, dO = (o2 - o1) * Math.PI / 180;
    var x = Math.sin(dA / 2) * Math.sin(dA / 2) +
            Math.cos(a1 * Math.PI / 180) * Math.cos(a2 * Math.PI / 180) *
            Math.sin(dO / 2) * Math.sin(dO / 2);
    return R * 2 * Math.atan2(Math.sqrt(x), Math.sqrt(1 - x));
  }

  /* ---------- regras (espelham o servidor) ------------------------------ */

  function conferir(arquivo, exif, dim) {
    var e = [];

    if (/^IMG-\d{8}-WA\d{4}\./i.test(arquivo.name) || /^WhatsApp Image/i.test(arquivo.name)) {
      e.push('Esta imagem veio do WhatsApp. O WhatsApp apaga a data e o local da foto. ' +
             'Envie o arquivo original, da galeria do celular que tirou a foto.');
      return e;
    }
    if (!exif || !exif.temExif) {
      e.push('A foto está sem os dados de origem (data, local e aparelho). ' +
             'Envie o arquivo original da galeria — não uma cópia recebida por aplicativo.');
      return e;
    }
    if (!exif.data) e.push('A foto não tem data de captura.');
    if (exif.lat === null || exif.lon === null) {
      e.push('A foto não tem localização. Ligue a marcação de local na câmera do celular.');
    } else {
      var d = distanciaKm(exif.lat, exif.lon, CFG.obraLat, CFG.obraLon);
      if (d > CFG.raioKm) e.push('A foto foi tirada a ' + d.toFixed(1).replace('.', ',') + ' km da obra.');
    }
    if (exif.data) {
      if (exif.data.getTime() > Date.now() + 864e5) {
        e.push('A data da foto está no futuro. Acerte o relógio do celular.');
      }
      var dias = Math.floor((Date.now() - exif.data.getTime()) / 864e5);
      if (dias > CFG.diasMaxAtras) e.push('Foto de ' + dias + ' dias atrás. Confirme se é deste trecho.');
    }
    if (dim) {
      var mp = (dim.w * dim.h) / 1e6;
      if (mp > 0 && mp < CFG.megapixelsMin) {
        e.push('Imagem reduzida: ' + dim.w + '×' + dim.h + ' px (' + mp.toFixed(1).replace('.', ',') +
               ' MP). A foto original de celular passa de ' + CFG.megapixelsMin + ' MP. ' +
               'Envie o arquivo original da galeria, em tamanho real.');
      }
    }
    return e;
  }

  /* ---------- interface ------------------------------------------------- */

  function caixa(campo) {
    var id = 'mu-val-' + (campo.name || 'midia').replace(/\W/g, '');
    var el = document.getElementById(id);
    if (!el) {
      el = document.createElement('div');
      el.id = id;
      el.style.cssText = 'margin:8px 0;font:13px/1.45 Arial,sans-serif';
      campo.parentNode.insertBefore(el, campo.nextSibling);
    }
    return el;
  }

  function pintar(el, itens) {
    el.innerHTML = '';
    itens.forEach(function (it) {
      var ok = it.erros.length === 0;
      var d = document.createElement('div');
      d.style.cssText = 'border-left:4px solid ' + (ok ? '#31A862' : '#C0392B') +
        ';background:' + (ok ? '#EAF6EF' : '#FDEDEC') + ';padding:8px 10px;margin-bottom:6px;border-radius:3px;color:#222';
      var t = document.createElement('strong');
      t.textContent = (ok ? '✓ ' : '✕ ') + it.nome;
      d.appendChild(t);
      it.erros.forEach(function (msg) {
        var p = document.createElement('div');
        p.style.cssText = 'margin-top:4px;font-size:12px';
        p.textContent = msg;
        d.appendChild(p);
      });
      el.appendChild(d);
    });
  }

  function ligar(campo, opcoes) {
    if (!campo) return;
    Object.assign(CFG, opcoes || {});
    var painel = caixa(campo);

    campo.addEventListener('change', function () {
      var arquivos = Array.prototype.slice.call(campo.files || []);
      if (!arquivos.length) { painel.innerHTML = ''; return; }

      var itens = [], pendentes = arquivos.length;
      arquivos.forEach(function (f, idx) {
        var leitor = new FileReader();
        leitor.onload = function () {
          var exif = null;
          try { exif = lerExif(leitor.result); } catch (err) { exif = null; }

          var img = new Image(), url = URL.createObjectURL(f);
          img.onload = function () {
            itens[idx] = { nome: f.name, erros: conferir(f, exif, { w: img.naturalWidth, h: img.naturalHeight }) };
            URL.revokeObjectURL(url);
            if (--pendentes === 0) pintar(painel, itens);
          };
          img.onerror = function () {
            itens[idx] = { nome: f.name, erros: conferir(f, exif, null) };
            URL.revokeObjectURL(url);
            if (--pendentes === 0) pintar(painel, itens);
          };
          img.src = url;
        };
        leitor.onerror = function () {
          itens[idx] = { nome: f.name, erros: ['Não foi possível ler o arquivo.'] };
          if (--pendentes === 0) pintar(painel, itens);
        };
        leitor.readAsArrayBuffer(f.slice(0, 256 * 1024));   // EXIF vive no começo
      });
    });
  }

  global.MUValidacao = { ligar: ligar, lerExif: lerExif, conferir: conferir, CFG: CFG };

})(window);
