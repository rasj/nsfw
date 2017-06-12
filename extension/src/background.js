let filterProxy = 'https://api.porndetector.ml/';
let iframeUrl = 'https://www.porndetector.ml/iframe.php';
let forceShow = '?&porn-block-show-image=1';
let checkExists = 'https://www.porndetector.ml/check-exists/';
let checkExistsLength = checkExists.length;

let ativo = false;
let filterProxyLength = filterProxy.length;
let forceShowLength = forceShow.length * -1;
let urlsOk = {}; 
let contextMenuID = null; 
let urlsOksPerTab = {};
let tabUrls = {};
let verificarSeExiste = false;

function limpatabsUrls(tabId)
{
  if(verificarSeExiste){
    for(var x in tabUrls[tabId]){
      urlsOksPerTab[x]--;
      if(!urlsOksPerTab[x]){
        delete urlsOksPerTab[x];
      }
    }
    delete tabUrls[tabId];
  }
}

chrome.runtime.onMessage.addListener(function(request, sender, sendResponse) {
  sendResponse({pornDetector: ativo});
});  

chrome.tabs.onRemoved.addListener(function(tabId,options){
  limpatabsUrls(tabId);
});

chrome.tabs.onUpdated.addListener(function(tabId,options){
  if(options.status == 'loading'){
    limpatabsUrls(tabId);
  }
});



function funcaoTabAtual(funcao)
{
  chrome.tabs.query({active: true, currentWindow: true}, function(tabs) {
      tabs.map((tabInfo)=>{
        funcao(tabInfo);
      });
  });
}

function createContextMenu(){
    if(contextMenuID){
      return;
    } 
    contextMenuID = {};
 
    try{
      contextMenuID.exibir = chrome.contextMenus.create({
          "title" : "Exibir",
          "type" : "normal",
          "contexts" : ["all"],
          "id" : "exibir",
          "onclick" : (info, tab) => {
            chrome.tabs.executeScript(tab.id,{file: 'updater.js',frameId: info.frameId},
              function(reloadResults){
                checkLastError('executeScript 2');
            });
          } 
      });

      contextMenuID.avaliar = chrome.contextMenus.create({
        "title": "Avaliar",
        "type" : "normal",
        "contexts" : ["all"],
        "id" : "avaliar",
        "onclick" : (info, tab) => {
            chrome.tabs.executeScript(tab.id,{file: 'avaliar.js',frameId: info.frameId},
              function(reloadResults){
                checkLastError('executeScript 2');
            });
        } 
      });

    }catch(ex){
      console.log('Erro ao criar..',ex);
    }
} 

function removeContextMenu()
{ 
  if(contextMenuID){
    for(let k in contextMenuID){
      chrome.contextMenus.remove(contextMenuID[k]);
    }
  }
  contextMenuID = null;
}

function getIsSFW(headers,name,startAt)
{
  if(headers[startAt] && headers[startAt].name == name){
      return headers[startAt].value === 'false';
  }
  for(var k in headers){
    if(headers[k].name == name){
      return headers[k].value === 'false';
    }
  }
  return true;
}



chrome.webRequest.onBeforeRequest.addListener(
  function(details) {
    let cancela = false;
    let retorno = {};
    let addPerTab = verificarSeExiste;
    if(ativo){

      if(verificarSeExiste){
          if(details.url.substr(0,checkExistsLength) === checkExists){
            details.url = details.url.substr(checkExistsLength);
            addPerTab = false;
            retorno = {
              redirectUrl: details.url
            }; 
            if(urlsOksPerTab[details.url+forceShow] ){
                retorno = {
                  redirectUrl: details.url+forceShow
                }; 
                return retorno;
            }
          }
      }
      
      if(details.url.substr(forceShowLength) === forceShow){
        urlsOk[details.url.substr(0,details.url.length + forceShowLength)] = details.url;
        if(addPerTab){
            if(details.url.indexOf(filterProxy) !== 0){
              if(!tabUrls[details.tabId]){
                tabUrls[details.tabId] = {};
              }
              if(!tabUrls[details.tabId][details.url]){
                tabUrls[details.tabId][details.url] = true;
                if(!urlsOksPerTab[details.url]){ 
                  urlsOksPerTab[details.url] = 1;
                }else{
                  urlsOksPerTab[details.url]++;
                }
              }
            }
        }
      }
      if(urlsOk[details.url]){
        if(urlsOk[details.url] !== true){
          delete urlsOk[urlsOk[details.url]];
        }
        delete urlsOk[details.url];
      }else{
        if(details.url.indexOf(filterProxy) !== 0){
          retorno = {
            redirectUrl: filterProxy+details.url
          }; 
        }
        if(cancela){
          retorno = {cancel:true};
        }
      }
    }
    return retorno;
  },{
    urls: ["<all_urls>"],
    types: ["image"]
  },
  ["blocking"]
); 

chrome.webRequest.onHeadersReceived.addListener(function(details){
  if(details.url.indexOf(filterProxy) === 0){
    var safe = getIsSFW(details.responseHeaders,'X-NSFW',5);
    if(safe){
      urlsOk[details.url.substr(filterProxyLength)] = true;
    }
  }
},{
  urls: ["<all_urls>"],
  types: ["image"]
},
["blocking","responseHeaders"]);
 
function checkLastError(msg)
{
  if (chrome.runtime.lastError) {
    console.log(chrome.runtime.lastError.message);
    console.log(msg);
    console.log('========');
  }
}

function setBadgeBackgroundColor(color)
{
    if(!color){
        color = 'FFFFFF';
    }
    var r = parseInt(color.substr(0,2),16);
    var g = parseInt(color.substr(2,2),16);
    var b = parseInt(color.substr(4,2),16);
    chrome.browserAction.setBadgeBackgroundColor({color:[r,g,b,255]});
}

function switchActive(msg)
{
    if(ativo){
      setBadgeBackgroundColor('F00F00');
      removeContextMenu();
      ativo = false;
    }else{
      setBadgeBackgroundColor('008000');
      chrome.browserAction.setTitle({title:'Ativado'});
      createContextMenu();
      ativo = true;
    }
    funcaoTabAtual(function(tabInfo){
      chrome.tabs.sendMessage(tabInfo.id, {pornDetector: ativo,showImage:false}, function(response) {
          
      });
    });
}

chrome.browserAction.setBadgeText({text:" "});
chrome.browserAction.onClicked.addListener(function(){
    switchActive('on clicked');
});

if(!ativo){
  switchActive('padrao');
}
