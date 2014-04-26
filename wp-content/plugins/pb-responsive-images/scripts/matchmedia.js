/*! matchMedia() polyfill - Test a CSS media type/query in JS. Authors & copyright (c) 2012: Scott Jehl, Paul Irish, Nicholas Zakas. Dual MIT/BSD license */
/* IE7/8 support added: https://github.com/benschwarz/matchMedia.js/commit/759810b55ffdf518d26ed87c95a6c1e1ec6ce1a1 */
window.matchMedia=window.matchMedia||function(e,t){var n=e.documentElement,r=n.firstElementChild||n.firstChild,i=e.createElement("body"),s=e.createElement("div");s.id="mq-test-1";s.style.cssText="position:absolute;top:-100em";i.style.background="none";i.appendChild(s);var o=function(e){s.innerHTML='­<style media="'+e+'"> #mq-test-1 { width: 42px; }</style>';n.insertBefore(i,r);bool=s.offsetWidth===42;n.removeChild(i);return{matches:bool,media:e}},u=function(){var t,r=n.body,i=false;s.style.cssText="position:absolute;font-size:1em;width:1em";if(!r){r=i=e.createElement("body");r.style.background="none"}r.appendChild(s);n.insertBefore(r,n.firstChild);if(i){n.removeChild(r)}else{r.removeChild(s)}t=a=parseFloat(s.offsetWidth);return t},a,f=o("(min-width: 0px)").matches;return function(t){if(f){return o(t)}else{var n=t.match(/\(min\-width:[\s]*([\s]*[0-9\.]+)(px|em)[\s]*\)/)&&parseFloat(RegExp.$1)+(RegExp.$2||""),r=t.match(/\(max\-width:[\s]*([\s]*[0-9\.]+)(px|em)[\s]*\)/)&&parseFloat(RegExp.$1)+(RegExp.$2||""),i=n===null,s=r===null,l=e.body.offsetWidth,c="em";if(!!n){n=parseFloat(n)*(n.indexOf(c)>-1?a||u():1)}if(!!r){r=parseFloat(r)*(r.indexOf(c)>-1?a||u():1)}bool=(!i||!s)&&(i||l>=n)&&(s||l<=r);return{matches:bool,media:t}}}}(document)