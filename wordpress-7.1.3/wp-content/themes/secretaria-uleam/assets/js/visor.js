// Visor de imágenes del contenido (ej. organigrama): clic para ampliar, zoom con rueda/botones/doble clic y arrastre para mover.
(function(){
  var imgs=document.querySelectorAll(".pagina-cont figure.wp-block-image img");
  if(!imgs.length&&!document.querySelector(".oc-av[data-grande]"))return;
  var S=function(p){return '<svg viewBox="0 0 24 24" aria-hidden="true">'+p+'</svg>'};
  var ic={lupa:S('<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3M11 8v6M8 11h6"/>'),mas:S('<path d="M12 5v14M5 12h14"/>'),menos:S('<path d="M5 12h14"/>'),
    bajar:S('<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>'),x:S('<path d="M18 6 6 18M6 6l12 12"/>')};
  var lb=document.createElement("div");lb.className="lb";lb.hidden=true;lb.setAttribute("role","dialog");lb.setAttribute("aria-modal","true");lb.setAttribute("aria-label","Visor de imagen");
  lb.innerHTML='<div class="lb-lienzo"><img alt=""></div><div class="lb-barra">'+
    '<button type="button" data-a="menos" aria-label="Alejar">'+ic.menos+'</button><span class="lb-pct">100%</span>'+
    '<button type="button" data-a="mas" aria-label="Acercar">'+ic.mas+'</button>'+
    '<a class="lb-bajar" download aria-label="Descargar">'+ic.bajar+'</a><button type="button" data-a="cerrar" aria-label="Cerrar">'+ic.x+'</button></div>'+
    '<p class="lb-ayuda">Rueda o doble clic para acercar · arrastra para mover · Esc para cerrar</p>';
  document.body.appendChild(lb);
  var lz=lb.querySelector(".lb-lienzo"),im=lz.querySelector("img"),pct=lb.querySelector(".lb-pct"),z=1,x=0,y=0,ult=null;
  function pinta(){ // limita el desplazamiento para que la imagen no salga de la pantalla
    var mx=Math.max(0,(im.offsetWidth*z-lz.clientWidth)/2),my=Math.max(0,(im.offsetHeight*z-lz.clientHeight)/2);
    x=Math.min(mx,Math.max(-mx,x));y=Math.min(my,Math.max(-my,y));im.classList.toggle("ampliada",z>1);
    im.style.transform="translate("+x+"px,"+y+"px) scale("+z+")";pct.textContent=Math.round(z*100)+"%"}
  function zoom(n,cx,cy){ // zoom manteniendo fijo el punto (cx,cy) relativo al centro
    n=Math.min(6,Math.max(1,n));if(cx===undefined){cx=0;cy=0}
    x=cx-(cx-x)*n/z;y=cy-(cy-y)*n/z;z=n;if(z===1){x=0;y=0}pinta();
  }
  function abrir(src,alt){im.src=src;im.alt=alt;lb.querySelector(".lb-bajar").href=src;z=1;x=0;y=0;pinta();lb.hidden=false;document.documentElement.classList.add("lb-abierto");ult=document.activeElement;lb.querySelector('[data-a="cerrar"]').focus()}
  function cerrar(){lb.hidden=true;document.documentElement.classList.remove("lb-abierto");if(ult)ult.focus()}
  imgs.forEach(function(i){
    var f=i.closest("figure");f.classList.add("zoomable");
    var b=document.createElement("button");b.type="button";b.className="zoom-btn";b.innerHTML=ic.lupa+"<span>Ver en grande</span>";f.appendChild(b);
    var go=function(){abrir(i.currentSrc||i.src,i.alt)};i.addEventListener("click",go);b.addEventListener("click",go);
  });
  // Fotos del personal (organigrama y carrusel del equipo): clic o Enter/Espacio abre la foto grande.
  var foto=function(e){var f=e.target.closest&&e.target.closest(".oc-av[data-grande]");if(f)abrir(f.dataset.grande,f.alt)};
  document.addEventListener("click",foto);
  document.addEventListener("keydown",function(e){if((e.key==="Enter"||e.key===" ")&&e.target.matches&&e.target.matches(".oc-av[data-grande]")){e.preventDefault();foto(e)}});
  lb.addEventListener("click",function(e){var a=e.target.closest("[data-a]");if(a){var k=a.dataset.a;k==="cerrar"?cerrar():zoom(z*(k==="mas"?1.4:1/1.4))}else if(e.target===lz||e.target===lb)cerrar()});
  var rel=function(e){var r=lz.getBoundingClientRect();return[e.clientX-r.left-r.width/2,e.clientY-r.top-r.height/2]};
  lz.addEventListener("wheel",function(e){e.preventDefault();var p=rel(e);zoom(z*(e.deltaY<0?1.15:1/1.15),p[0],p[1])},{passive:false});
  im.addEventListener("dblclick",function(e){var p=rel(e);zoom(z>1?1:2.5,p[0],p[1])});
  var arr=null;
  im.addEventListener("pointerdown",function(e){if(z===1)return;arr=[e.clientX-x,e.clientY-y];im.setPointerCapture(e.pointerId);im.classList.add("mov")});
  im.addEventListener("pointermove",function(e){if(!arr)return;x=e.clientX-arr[0];y=e.clientY-arr[1];pinta()});
  im.addEventListener("pointerup",function(){arr=null;im.classList.remove("mov")});
  im.addEventListener("click",function(e){e.stopPropagation()});
  document.addEventListener("keydown",function(e){if(lb.hidden)return;if(e.key==="Escape")cerrar();else if(e.key==="+"||e.key==="=")zoom(z*1.4);else if(e.key==="-")zoom(z/1.4);else if(e.key==="0")zoom(1)});
})();
