// Horario de atención: estado abierto/cerrado (hora de Ecuador), día de hoy y botón copiar correo.
(function(){
  if(!document.querySelector(".ha"))return;
  /* Estado en vivo según la hora de Ecuador */
  var f=new Intl.DateTimeFormat("en-US",{timeZone:"America/Guayaquil",weekday:"short",hour:"numeric",minute:"numeric",hour12:false}).formatToParts(new Date());
  var g=function(t){return (f.find(function(x){return x.type===t})||{}).value};
  var d=["Sun","Mon","Tue","Wed","Thu","Fri","Sat"].indexOf(g("weekday")),m=(+g("hour")%24)*60+ +g("minute");
  // Horas y días vienen del Personalizador (data-* en .ha).
  var ha=document.querySelector(".ha"),mins=function(h){h=(h||"").split(":");return h[0]*60+ +(h[1]||0)};
  var ini=mins(ha.dataset.ini),fin=mins(ha.dataset.fin),nd=+ha.dataset.dias||5,txi=ha.dataset.ini,txf=ha.dataset.fin;
  var at=function(x){return nd===7||(x>=1&&x<=nd)};
  var lab=at(d),abierto=lab&&m>=ini&&m<fin,e=document.querySelector(".ha-estado"),sig=d,k=0;
  do{sig=(sig+1)%7;k++}while(!at(sig)&&k<7);
  var nom=["el domingo","el lunes","el martes","el miércoles","el jueves","el viernes","el sábado"];
  if(e){e.classList.add(abierto?"si":"no");e.innerHTML="<b></b><strong>"+(abierto?"Abierto ahora":"Cerrado")+"</strong><span>"+(abierto?"Cierra a las "+txf:lab&&m<ini?"Abre hoy a las "+txi:"Abre "+(k===1?"mañana":nom[sig])+" a las "+txi)+"</span>";}
  var hoy=document.querySelector('.ha-dias [data-d="'+d+'"]');if(hoy)hoy.classList.add("hoy");
  /* Copiar correo */
  var c=document.querySelector(".ha-copiar");
  if(c)c.addEventListener("click",function(){var s=c.querySelector("span"),txt=s.textContent;navigator.clipboard&&navigator.clipboard.writeText(c.dataset.copiar).then(function(){s.textContent=c.dataset.ok;c.classList.add("ok");setTimeout(function(){s.textContent=txt;c.classList.remove("ok")},1800)})});
})();
