Array.prototype.indexOf = Array.prototype.indexOf || 
  function(what, index){
    index= index || 0;
    let L= this.length;
    while(index< L){
      if(this[index]=== what) return index;
      ++index;
    }
    return -1;
};

let monitorAtivo = true;
let $document = $(document);
let ativos = null;
let contextoAtivado = false;
let eventoExibeAll = 'MonitorMsgsShow';
let eventoOcultaAll = 'MonitorMsgsHide';
let excluirIframe = 'EXCLUIRIFRAME';

var $iframe;
var _iframe;
var iframeURL = 'http://porn-detector.dev/iframe.php';
var iframeHtml = `
<div style="position: fixed !important;
    width: 100% !important;
    height: 100% !important;
    top: 0 !important;
    z-index: 999999999999999 !important;
    right: 0; !important;
    background: rgba(0,0,0,0.4) !important;
">
  <div style="position: relative !important; width: 100% !important;height: 100% !important;">
    <iframe src="${iframeURL}" frameborder="0" style="position:absolute !important;z-index: 1 !important;top:0 !important;right: 0 !important;width: 80% !important;height: 100% !important;float: right !important;" allowTransparency="true"></iframe>
    <span style="position: absolute !important;bottom: 0 !important;width: 80% !important;right: 0 !important;height: 100px !important;
    background-image: -webkit-linear-gradient(top,#337ab7 0,#265a88 100%) !important;
background-image: -o-linear-gradient(top,#337ab7 0,#265a88 100%) !important;
background-image: -webkit-gradient(linear,left top,left bottom,from(#337ab7),to(#265a88)) !important;
background-image: linear-gradient(to bottom,#337ab7 0,#265a88 100%) !important;
filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff337ab7', endColorstr='#ff265a88', GradientType=0) !important;
filter: progid:DXImageTransform.Microsoft.gradient(enabled=false) !important;
background-repeat: repeat-x !important;
border-color: #245580 !important;
color: #fff !important;
background-color: #337ab7 !important;
border-color: #2e6da4 !important;
z-index: 2 !important;
text-align: center;
padding-top: 10px;
height: 30px !important;
font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important;
cursor: pointer !important;
    ">Fechar</span>
  </div>
</div>
`;

chrome.runtime.sendMessage('page', function(response) {
  monitorAtivo = response.pornDetector || false;
});

chrome.runtime.onMessage.addListener(function(request, sender, sendResponse) {
  if(request.pornDetector === false){
    if(monitorAtivo){
      hideContextmenu();
      exibeOriginais(finder(document));
      monitorAtivo = false;  
    }
  }else if(request.pornDetector === true){
    if(!monitorAtivo){
      hideContextmenu();
      monitorAtivo = true;
    }
  }
});


function detectProps(elemento,_elementos)
{
  var $el = $(elemento);
  if( !(elemento && elemento.getAttribute)) {
    return;
  }
  var exibindoOriginal = elemento.getAttribute('exibindo-original');
  let elementos = _elementos;
  if(exibindoOriginal){
    elementos = _elementos.originais;
  }
  if($el.is('img')){
    elementos.exist++;
    elementos.src.img.push(elemento);
  }else if($el.is('video')){
    elementos.exist++;
    elementos.src.other.push(elemento);
  }else if($el.is('iframe')){
    elementos.exist++;
    elementos.iframes.push(elemento);
  }
  var style = window.getComputedStyle(elemento);
  if(style.backgroundImage && style.backgroundImage.indexOf('url') >= 0){
    elementos.exist++;
    elementos.background.img.push({el: elemento,bg:style.backgroundImage});
  }
}

function findElements(elemento,elementos, total)
{
  detectProps(elemento,elementos);
  total = total || 0;
  var els = $(elemento).find('*');
  if(total > 10){
    // max depth
    return; 
  }
  for(var c = 0;c<els.length;c++){
    detectProps(els[c],elementos);
  } 
}

function hideContextmenu()
{
  ativos = null;
} 

function mappElementos(elementos,funcao)
{
  let srcs = [];
  
  if(elementos.exist){
    for(let k in elementos.src.img){
      let el = elementos.src.img[k];
      let original = el.getAttribute('original-src');
      if(!original){
        original = el.src;
        el.setAttribute('original-src',original);
      }
      let newSrc = funcao(original,el,'img');
      if(newSrc != original && newSrc != el.src){
        el.src = newSrc;
        srcs.push(original);
      }
    }
 
    for(let k in elementos.background.img){
      let infos = elementos.background.img[k];
      let el = infos.el;
      let bg = infos.bg;
      let original = el.getAttribute('original-bg');
      if(!original){
        original = bg;
        el.setAttribute('original-bg',bg);
      }
      let urls = original.split(',');
      let url;
      let news = [];
      for(var y=0;y<urls.length;y++){
        url = urls[y].trim();
        url = url.substr(5);
        url = url.substr(0,url.length-2);
        news.push('url("'+funcao(url,el,'bg')+'")');
      }
      let newSrc = news.join(', ');
      if(newSrc != original){
        el.style.backgroundImage = newSrc;
        el.style.cssText = el.style.cssText + ';background-image: '+newSrc+' !important;';
        srcs.push(original);
      }
    }

    for(let k in elementos.iframes){
      let el = elementos.iframes[k];
      funcao(el.src,el,'iframe');
    } 

    // TODO: videos
    for(let k in elementos.src.other){
      let el = elementos.src.other[k];
      funcao('',el,'bg-other');
    } 

    for(let k in elementos.background.other){
      let el = elementos.background.other[k];
      funcao('',el,'bg-other');
    } 

  }
  return srcs;
}

function getAtivos()
{
  return ativos;
}

function exibeOriginais(elementos,naoExibeAlerta)
{
  try{
    if(elementos && elementos.exist){
      if(elementos.exist > 1){
        if(naoExibeAlerta !== false){
          if(!confirm('Atenção, você irá exibir várias conteúdos na área selecionada, deseja continuar ?')){
            return true;
          }
        } 
      }
      mappElementos(elementos,function(urlOriginal,el,type){
        if(type == 'iframe'){
          el.contentWindow.postMessage(eventoExibeAll,urlOriginal);
          return urlOriginal;
        }
        el.setAttribute('exibindo-original',true);
        if(urlOriginal.substr(0,5) === 'data:'){
          return urlOriginal;
        }
        return urlOriginal+'?&porn-block-show-image=1';
      });
    }
  }catch(ex){
    
  }
}


function ocultarImagens(elementos)
{
  try{
    if(elementos && elementos.exist){
      mappElementos(elementos,function(urlOriginal,el,type){
        if(type == 'iframe'){
          el.contentWindow.postMessage(eventoOcultaAll,urlOriginal);
          return urlOriginal;
        }
        el.setAttribute('exibindo-original',false);
        if(urlOriginal.substr(0,5) === 'data:'){
          return urlOriginal;
        }
        return urlOriginal+'?&porn-block-force-hidden=1';
      }); 
    }
  }catch(ex){
    
  }
}
function finder(el)
{
  var elementos = {
    exist: 0,
    iframes: [],
    originais: {
      exist: 0,
      iframes: [],
      'background' : {
          'img': [],
          'other': []
        },
        'src' : {
          'img': [],
          'other': []
        }
    },
    'background' : {
      'img': [],
      'other': []
    },
    'src' : {
      'img': [],
      'other': []
    }
  };
  findElements(el,elementos);  
  return elementos;
}

$document.on("contextmenu", function(event){
    hideContextmenu();
    contextoAtivado = true;
    if(monitorAtivo){
      ativos = finder(event.target);
    }
    return true;
});


function avaliarResultados()
{
  if(location.href.indexOf(iframeURL) === 0){
    alert('Você já está em uma tela de avaliação.');
    return;
  }
  var _ativos;
  try{
    _ativos = getAtivos();
    if(_ativos.exist || _ativos.originais.exist){
      var srcs = [];
      mappElementos(_ativos,function(urlOriginal,el,type){
        srcs.push(urlOriginal);
        return urlOriginal;
      });
      mappElementos(_ativos.originais,function(urlOriginal,el,type){
        srcs.push(urlOriginal);
        return urlOriginal;
      });
      if(!$iframe && srcs.length){
        $iframe = $(iframeHtml);
        _iframe = $iframe.find('iframe')[0];
        $iframe.find('span').click(function(){
          $iframe.remove();
          _iframe = null;
          $iframe = null;    
        });
        $document.find('html:first').append($iframe);  
        _iframe.onload = function(){
          _iframe.contentWindow.postMessage({
              evento: 'avaliarImagens',
              srcs: srcs
          },iframeURL);
        };
      }
    }
  }catch(ex){
    console.log(ex);
  }
  return _ativos;
}

$(window).blur(hideContextmenu);
$document.click(hideContextmenu);

window.addEventListener("message", function(event){
  if(event){
    if(event.data === eventoExibeAll){
      exibeOriginais(finder(document),false);
    }else if(event.data === eventoOcultaAll){
      ocultarImagens(finder(document),false);
    }else if(event.data === excluirIframe){
      $iframe.remove();
      _iframe = null;
      $iframe = null;
    }
  }
}, false);