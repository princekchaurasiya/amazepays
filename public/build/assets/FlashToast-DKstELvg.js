import{b as d,r as o,j as s}from"./app-BWQT48vS.js";import{T as g}from"./triangle-alert-DwCok4Uj.js";import{c as a}from"./createLucideIcon-BWn1xYG1.js";import{X as m}from"./x-B9IFMMhJ.js";/**
 * @license lucide-react v0.400.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const p=a("CircleCheckBig",[["path",{d:"M22 11.08V12a10 10 0 1 1-5.93-9.14",key:"g774vq"}],["path",{d:"m9 11 3 3L22 4",key:"1pflzl"}]]);/**
 * @license lucide-react v0.400.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const x=a("CircleX",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"m15 9-6 6",key:"1uzhvr"}],["path",{d:"m9 9 6 6",key:"z0biqf"}]]),u={success:p,error:x,warning:g},f={success:"bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200",error:"bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200",warning:"bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200"};let h=0;function j(){const{flash:t}=d().props,[i,n]=o.useState([]);o.useEffect(()=>{const e=[];["success","error","warning"].forEach(r=>{t!=null&&t[r]&&e.push({id:h++,type:r,message:t[r]})}),e.length>0&&n(r=>[...r,...e])},[t]),o.useEffect(()=>{if(i.length===0)return;const e=setTimeout(()=>{n(r=>r.slice(1))},5e3);return()=>clearTimeout(e)},[i]);const c=e=>{n(r=>r.filter(l=>l.id!==e))};return i.length===0?null:s.jsx("div",{className:"fixed top-4 right-4 z-[100] space-y-2 max-w-sm w-full pointer-events-none",children:i.map(e=>{const r=u[e.type];return s.jsxs("div",{className:`pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-lg border shadow-lg animate-slide-in-right ${f[e.type]}`,children:[s.jsx(r,{size:18,className:"flex-shrink-0 mt-0.5"}),s.jsx("p",{className:"flex-1 text-sm",children:e.message}),s.jsx("button",{onClick:()=>c(e.id),className:"flex-shrink-0 opacity-60 hover:opacity-100",children:s.jsx(m,{size:14})})]},e.id)})})}export{j as F};
