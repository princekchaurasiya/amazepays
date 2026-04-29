import{c as p}from"./createLucideIcon-BRREqyix.js";import{j as c}from"./app-BsdrNPsM.js";/**
 * @license lucide-react v0.400.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const u=p("Upload",[["path",{d:"M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4",key:"ih7n3h"}],["polyline",{points:"17 8 12 3 7 8",key:"t8dd8p"}],["line",{x1:"12",x2:"12",y1:"3",y2:"15",key:"widbto"}]]);function k({disabled:r,isDragging:o,error:e,compact:i=!1,onClick:d,onKeyDown:a,onDragOver:n,onDragLeave:t,onDrop:g,ariaLabel:b,className:s="",children:l}){return c.jsx("div",{role:"button",tabIndex:r?-1:0,"aria-label":b,"aria-disabled":r,onClick:()=>{r||d()},onKeyDown:a,onDragOver:n,onDragLeave:t,onDrop:g,className:`
                group relative w-full rounded-xl border-2 border-dashed text-left transition-all outline-none
                focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900
                flex items-center justify-center gap-2
                ${i?"py-6 px-4":"py-8 px-4"}
                ${r?"cursor-not-allowed border-gray-200 bg-gray-50 opacity-60 dark:border-gray-700 dark:bg-gray-800/50":o?"border-indigo-500 bg-indigo-50/80 dark:border-indigo-400 dark:bg-indigo-950/40":e?"border-red-300 bg-red-50/30 dark:border-red-800 dark:bg-red-950/20":"cursor-pointer border-gray-300 bg-white hover:border-indigo-400 dark:border-gray-600 dark:bg-gray-900/40 dark:hover:border-indigo-500/50"}
                ${s}
            `,children:l})}export{k as A,u as U};
