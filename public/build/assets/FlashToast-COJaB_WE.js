import{d,r as i,j as s}from"./app-DBpRFu2S.js";import{T as g}from"./triangle-alert-Bkm07Tgd.js";import{c as o}from"./createLucideIcon-CDVtevm4.js";/**
 * @license lucide-react v0.400.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const m=o("CircleCheckBig",[["path",{d:"M22 11.08V12a10 10 0 1 1-5.93-9.14",key:"g774vq"}],["path",{d:"m9 11 3 3L22 4",key:"1pflzl"}]]);/**
 * @license lucide-react v0.400.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const p=o("CircleX",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"m15 9-6 6",key:"1uzhvr"}],["path",{d:"m9 9 6 6",key:"z0biqf"}]]);/**
 * @license lucide-react v0.400.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const x=o("X",[["path",{d:"M18 6 6 18",key:"1bl5f8"}],["path",{d:"m6 6 12 12",key:"d8bk6v"}]]),h={success:m,error:p,warning:g},u={success:"bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200",error:"bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200",warning:"bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200"};let f=0;function w(){const{flash:t}=d().props,[n,a]=i.useState([]);i.useEffect(()=>{const e=[];["success","error","warning"].forEach(r=>{t!=null&&t[r]&&e.push({id:f++,type:r,message:t[r]})}),e.length>0&&a(r=>[...r,...e])},[t]),i.useEffect(()=>{if(n.length===0)return;const e=setTimeout(()=>{a(r=>r.slice(1))},5e3);return()=>clearTimeout(e)},[n]);const c=e=>{a(r=>r.filter(l=>l.id!==e))};return n.length===0?null:s.jsx("div",{className:"fixed top-4 right-4 z-[100] space-y-2 max-w-sm w-full pointer-events-none",children:n.map(e=>{const r=h[e.type];return s.jsxs("div",{className:`pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-lg border shadow-lg animate-slide-in-right ${u[e.type]}`,children:[s.jsx(r,{size:18,className:"flex-shrink-0 mt-0.5"}),s.jsx("p",{className:"flex-1 text-sm",children:e.message}),s.jsx("button",{onClick:()=>c(e.id),className:"flex-shrink-0 opacity-60 hover:opacity-100",children:s.jsx(x,{size:14})})]},e.id)})})}export{w as F,x as X};
