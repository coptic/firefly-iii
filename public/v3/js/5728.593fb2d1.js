"use strict";(self["webpackChunkfirefly_iii"]=self["webpackChunkfirefly_iii"]||[]).push([[5728],{5728:(e,t,a)=>{a.r(t),a.d(t,{default:()=>T});var s=a(9835);const o={key:0},n={key:1};function l(e,t,a,l,i,r){const c=(0,s.up)("NewUser"),u=(0,s.up)("Dashboard"),d=(0,s.up)("q-page");return(0,s.wg)(),(0,s.j4)(d,null,{default:(0,s.w5)((()=>[0===e.assetCount?((0,s.wg)(),(0,s.iD)("div",o,[(0,s.Wm)(c,{onCreatedAccounts:e.refreshThenCount},null,8,["onCreatedAccounts"])])):(0,s.kq)("",!0),e.assetCount>0?((0,s.wg)(),(0,s.iD)("div",n,[(0,s.Wm)(u)])):(0,s.kq)("",!0)])),_:1})}a(702);var i=a(3836),r=a(3555);const c={class:"q-ma-md"},u={class:"row q-mb-sm"},d={class:"col"},m={class:"col"},f={class:"col"},h={class:"row q-mb-sm"},p={class:"col"},b={class:"row q-mb-sm"},v={class:"col"},g={class:"row q-mb-sm"},w={class:"col"},y=(0,s._)("div",{class:"col"}," Category box ",-1),C=(0,s.uE)('<div class="row q-mb-sm"><div class="col"> Expense Box </div><div class="col"> Revenue Box </div></div><div class="row q-mb-sm"><div class="col"> Piggy box </div><div class="col"> Bill box </div></div>',2);function q(e,t,a,o,n,l){const i=(0,s.up)("BillInsightBox"),r=(0,s.up)("SpendInsightBox"),q=(0,s.up)("NetWorthInsightBox"),_=(0,s.up)("AccountChart"),W=(0,s.up)("TransactionLists"),x=(0,s.up)("BudgetBox"),B=(0,s.up)("q-fab-action"),A=(0,s.up)("q-fab"),k=(0,s.up)("q-page-sticky");return(0,s.wg)(),(0,s.iD)("div",c,[(0,s._)("div",u,[(0,s._)("div",d,[(0,s.Wm)(i)]),(0,s._)("div",m,[(0,s.Wm)(r)]),(0,s._)("div",f,[(0,s.Wm)(q)])]),(0,s._)("div",h,[(0,s._)("div",p,[(0,s.Wm)(_)])]),(0,s._)("div",b,[(0,s._)("div",v,[(0,s.Wm)(W)])]),(0,s._)("div",g,[(0,s._)("div",w,[(0,s.Wm)(x)]),y]),C,(0,s.Wm)(k,{offset:[18,18],position:"bottom-right"},{default:(0,s.w5)((()=>[(0,s.Wm)(A,{color:"green",direction:"up",icon:"fas fa-chevron-up",label:"Actions","label-position":"left",square:"","vertical-actions-align":"right"},{default:(0,s.w5)((()=>[(0,s.Wm)(B,{label:e.$t("firefly.new_budget"),to:{name:"budgets.create"},color:"primary",icon:"fas fa-chart-pie",square:""},null,8,["label","to"]),(0,s.Wm)(B,{label:e.$t("firefly.new_asset_account"),to:{name:"accounts.create",params:{type:"asset"}},color:"primary",icon:"far fa-money-bill-alt",square:""},null,8,["label","to"]),(0,s.Wm)(B,{label:e.$t("firefly.newTransfer"),to:{name:"transactions.create",params:{type:"transfer"}},color:"primary",icon:"fas fa-exchange-alt",square:""},null,8,["label","to"]),(0,s.Wm)(B,{label:e.$t("firefly.newDeposit"),to:{name:"transactions.create",params:{type:"deposit"}},color:"primary",icon:"fas fa-long-arrow-alt-right",square:""},null,8,["label","to"]),(0,s.Wm)(B,{label:e.$t("firefly.newWithdrawal"),to:{name:"transactions.create",params:{type:"withdrawal"}},color:"primary",icon:"fas fa-long-arrow-alt-left",square:""},null,8,["label","to"])])),_:1})])),_:1})])}const _={name:"Dashboard",components:{TransactionLists:(0,s.RC)((()=>a.e(936).then(a.bind(a,936)))),AccountChart:(0,s.RC)((()=>Promise.all([a.e(4736),a.e(7243)]).then(a.bind(a,7243)))),NetWorthInsightBox:(0,s.RC)((()=>Promise.all([a.e(4736),a.e(7224)]).then(a.bind(a,7224)))),BillInsightBox:(0,s.RC)((()=>Promise.all([a.e(4736),a.e(4568)]).then(a.bind(a,4568)))),SpendInsightBox:(0,s.RC)((()=>Promise.all([a.e(4736),a.e(8815)]).then(a.bind(a,8815)))),BudgetBox:(0,s.RC)((()=>Promise.all([a.e(4736),a.e(7886)]).then(a.bind(a,8482))))}};var W=a(1639),x=a(3388),B=a(9361),A=a(935),k=a(9984),P=a.n(k);const I=(0,W.Z)(_,[["render",q]]),R=I;P()(_,"components",{QPageSticky:x.Z,QFab:B.Z,QFabAction:A.Z});const Z=(0,s.aZ)({name:"PageIndex",components:{Dashboard:R,NewUser:(0,s.RC)((()=>Promise.all([a.e(4736),a.e(3064),a.e(5389)]).then(a.bind(a,5389))))},data(){return{assetCount:1,$store:null}},mounted(){this.countAssetAccounts()},methods:{refreshThenCount:function(){this.$store=(0,r.S)(),this.$store.refreshCacheKey(),this.countAssetAccounts()},countAssetAccounts:function(){let e=new i.Z;e.list("asset",1,this.getCacheKey).then((e=>{this.assetCount=parseInt(e.data.meta.pagination.total)}))}}});var $=a(9885);const D=(0,W.Z)(Z,[["render",l]]),T=D;P()(Z,"components",{QPage:$.Z})}}]);