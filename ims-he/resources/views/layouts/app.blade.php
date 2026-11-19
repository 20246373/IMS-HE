<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'IMS-HE') - Ibay Enterprises</title>
<style>
 body{font-family:system-ui,sans-serif;margin:0;background:#f4f5f7;color:#222}
 nav{background:#1f3a5f;color:#fff;padding:.7rem 1.2rem;display:flex;gap:1rem;align-items:center;flex-wrap:wrap}
 nav a{color:#fff;text-decoration:none} nav .sp{flex:1}
 nav.staff-nav{position:fixed;z-index:20;inset:0 auto 0 0;width:212px;box-sizing:border-box;flex-direction:column;flex-wrap:nowrap;align-items:stretch;gap:.35rem;padding:1.5rem 1rem 1rem;background:linear-gradient(165deg,#172d49 0%,#1f3a5f 58%,#25466d 100%);box-shadow:8px 0 28px #14253a20;overflow-y:auto;overflow-x:hidden}
 nav.staff-nav > strong{display:flex;align-items:center;gap:.7rem;font-size:1.05rem;letter-spacing:.06em;padding:.3rem .65rem 1.45rem}
 nav.staff-nav > strong:before{content:"I";display:grid;place-items:center;width:34px;height:34px;border-radius:11px;background:#d8e8fb;color:#1f3a5f;font-size:1.05rem;font-weight:800;letter-spacing:0;box-shadow:0 3px 10px #071b3333}
 nav.staff-nav > a{display:flex;align-items:center;min-height:42px;padding:0 .8rem;border-radius:99px;color:#d9e4f1;font-size:.94rem;font-weight:500;transition:background .16s ease,color .16s ease,transform .16s ease}
 nav.staff-nav > a:hover,nav.staff-nav .nav-dropdown > div a:hover{background:#ffffff14;color:#fff;transform:translateX(2px)}
 nav.staff-nav > a.nav-link-active,nav.staff-nav .nav-dropdown > div a.nav-link-active{background:#dceafe;color:#19395e;font-weight:650;box-shadow:0 3px 10px #0c1e342b}
 nav.staff-nav .nav-section{padding:1.1rem .8rem .4rem;color:#d3e0ef;font-size:.8rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase}
 nav.staff-nav .nav-sp{flex:1;min-height:1rem}
 nav.staff-nav .nav-dropdown{border-radius:9px}
 nav.staff-nav .nav-dropdown summary{position:relative;display:flex;align-items:center;min-height:42px;padding:0 2rem 0 .8rem;border-radius:9px;color:#d9e4f1;font-size:.94rem;font-weight:500;transition:background .16s ease,color .16s ease}
 nav.staff-nav .nav-dropdown summary:hover{background:#ffffff14;color:#fff}
 nav.staff-nav .nav-dropdown summary:after{content:"";position:absolute;right:.95rem;width:7px;height:7px;border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;transform:rotate(45deg) translateY(-2px);transition:transform .16s ease}
 nav.staff-nav .nav-dropdown[open] summary:after{transform:rotate(225deg) translate(-1px,-1px)}
 nav.staff-nav .nav-dropdown > div{position:static;min-width:0;margin:.2rem 0 .35rem .8rem;padding:.25rem 0 .25rem .65rem;border-left:1px solid #ffffff30;box-shadow:none;background:transparent;gap:.15rem}
 nav.staff-nav .nav-dropdown > div a{display:flex;align-items:center;min-height:36px;padding:0 .7rem;border-radius:7px;color:#c2d1e3;font-size:.88rem;transition:background .16s ease,color .16s ease,transform .16s ease}
 nav.staff-nav .nav-user{display:grid;gap:.18rem;margin:.25rem 0 .55rem;padding:.8rem .85rem;border:1px solid #ffffff18;border-radius:10px;background:#ffffff0b}
 nav.staff-nav .nav-user{min-width:0}
 nav.staff-nav .nav-user strong,nav.staff-nav .nav-user span{min-width:0;overflow-wrap:anywhere}
 nav.staff-nav .nav-user strong{color:#fff;font-size:.88rem;font-weight:600}
 nav.staff-nav .nav-user span{color:#aebfd3;font-size:.76rem}
 nav.staff-nav form{margin:0}
 nav.staff-nav form button{width:100%;min-height:40px;background:#b42318;color:#fff;text-align:center;padding:0 .85rem;border:0;border-radius:9px;font-size:.9rem;font-weight:600}
 nav.staff-nav form button:hover{background:#8f1c13;color:#fff}
 body.staff-layout main{width:calc(100% - 260px);max-width:1120px;margin:1.6rem 1.5rem 1.5rem 236px;padding:0}
 @media (max-width:700px){
  nav.staff-nav{width:190px;padding:1rem .7rem .8rem}
  body.staff-layout main{width:calc(100% - 216px);margin:1rem .8rem 1rem 202px}
 }
 
 @media (max-width:520px){
  nav.staff-nav{position:sticky;inset:auto;width:100%;height:auto;max-height:48vh;border-radius:0 0 16px 16px;padding:.7rem;gap:.2rem}
  nav.staff-nav > strong{padding:.1rem .55rem .55rem}
  nav.staff-nav .nav-section,nav.staff-nav .nav-sp,nav.staff-nav .nav-user{display:none}
  nav.staff-nav > a,nav.staff-nav .nav-dropdown summary{min-height:36px;font-size:.88rem}
  nav.staff-nav .nav-dropdown > div{margin-left:.55rem}
  nav.staff-nav form button{min-height:36px}
  body.staff-layout main{width:auto;max-width:none;margin:1rem .8rem;padding:0}
 }
 .nav-dropdown{position:relative}
 .nav-dropdown summary{cursor:pointer;list-style:none}
 .nav-dropdown summary::-webkit-details-marker{display:none}
 .nav-dropdown > div{position:absolute;z-index:10;top:calc(100% + .7rem);left:0;min-width:10rem;background:#1f3a5f;padding:.7rem;display:grid;gap:.7rem;border-radius:0 0 5px 5px;box-shadow:0 2px 6px #0003}
 main{max-width:1000px;margin:1.2rem auto;padding:0 1rem}
 .card{background:#fff;border:1px solid #dde2e8;border-radius:12px;padding:1rem 1.2rem;margin-bottom:1rem;box-shadow:none}
 table{width:100%;border-collapse:collapse} th,td{padding:.45rem .5rem;border-bottom:1px solid #eee;text-align:left}
 input,select,textarea{padding:.4rem;margin:.2rem 0;max-width:100%}
 input,select,textarea,button{font-family:inherit}   /* form controls use the page font, not the browser default */
  /* :where() keeps this rule weak, so boxes inside the stepper and the ₱ prefix keep their own (borderless) look */
 input:where(:not([type=checkbox],[type=radio],[type=hidden])),select,textarea{border:1px solid #767676;border-radius:8px;background:#fff;color:#222} label{display:block;margin-top:.5rem;font-size:.9rem}
 .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem 1rem}
 .field{min-width:0}.field label{margin:0 0 .25rem}.field input,.field select,.field textarea{box-sizing:border-box;width:100%;height:2.35rem;margin:0}
 .field textarea{height:auto;min-height:5rem}.required:after{content:" *";color:#b42318}.optional{color:inherit}
 .input-with-prefix{display:flex;align-items:center;box-sizing:border-box;border:1px solid #767676;border-radius:8px;background:#fff;overflow:hidden}
 .input-with-prefix > span{padding:0 .45rem;color:#555;font-size:.9rem}
 .input-with-prefix input{box-sizing:border-box;width:100%;min-width:0;height:2.35rem;margin:0;border:0;background:transparent}
  /* Read-only values read as plain text, not as a grayed-out box */
 .field input[readonly],.field textarea[readonly]{border:0;background:transparent;padding-left:0;font-weight:600;color:inherit;outline:0}
 input[readonly],textarea[readonly]{background:#eef0f3;color:#666}.field-error{margin-top:.25rem;color:#b42318;font-size:.82rem}
 .required-note{margin:0 0 .9rem;color:#555;font-size:.85rem}.required-note span{color:#b42318;font-weight:700}   /* "* Required" legend, only on forms that mix required and optional fields */
 .alert{display:flex;align-items:flex-start;gap:.7rem;padding:.75rem 1rem;margin-bottom:1rem;border:1px solid;border-radius:7px}.alert-success{background:#e8f7ed;border-color:#98d5aa;color:#176b35}.alert-error{background:#fcedeb;border-color:#e7a39c;color:#8a1f17}.alert-warning{background:#fff5d8;border-color:#e8c66d;color:#785b00}.alert-icon svg{display:block;width:1.2rem;height:1.2rem}.alert-body{flex:1}.alert-body strong{display:block;margin-bottom:.15rem}.alert-body ul{margin:.25rem 0 0;padding-left:1.2rem}.alert-dismiss{padding:0;background:transparent;color:currentColor;font-size:1.2rem;line-height:1;border:0}
 @media (max-width:700px){.form-grid{grid-template-columns:1fr}}
 button,.btn{background:#1f3a5f;color:#fff;border:0;border-radius:8px;padding:.45rem .9rem;cursor:pointer;text-decoration:none;display:inline-block;box-sizing:border-box;font:inherit;font-size:.9rem;line-height:1.25;vertical-align:middle}
 .danger{background:#a33}.ok{background:#e6f4ea;padding:.6rem;border-radius:5px;margin-bottom:1rem}
 .err{background:#fdecea;padding:.6rem;border-radius:5px;margin-bottom:1rem;color:#8a1f17}
 .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem}.low{color:#a33;font-weight:600}
 .inline{display:inline;margin:0}
 /* Login: access chooser + forms, built on .card */
 .auth-top{display:flex;justify-content:space-between;font-size:.9rem;color:#666;margin-bottom:.4rem}
 .auth-top a{color:#1f3a5f;text-decoration:none}
 .auth input{display:block;width:100%;box-sizing:border-box}
 .auth .btn{display:block;text-align:center;margin-bottom:.5rem}
 .btn-alt{background:#fff;color:#1f3a5f;border:1px solid #1f3a5f}
 .btn-alt,.btn.muted{padding:calc(.45rem - 1px) calc(.9rem - 1px)}   /* same height as the solid buttons */
 /* Whole card is a link (dashboard summary cards) */
 .card-link{display:block;color:inherit;text-decoration:none;transition:box-shadow .15s ease,transform .15s ease}
 .card-link:hover{box-shadow:0 3px 10px #0002;transform:translateY(-1px)}
 /* Row of action buttons in a table: every button the same size */
 .actions{display:flex;gap:.4rem;flex-wrap:wrap} .actions form{margin:0}
 .actions .btn,.actions button{box-sizing:border-box;min-width:5.5rem;height:2.1rem;margin:0;padding:0 .8rem;display:inline-flex;align-items:center;justify-content:center;font:inherit;font-size:.9rem;line-height:1;vertical-align:top}
 form > .actions{margin-top:1rem}
 .filter-buttons{display:flex;gap:.4rem;align-items:end}
 .filter-buttons .btn,.filter-buttons button{box-sizing:border-box;height:2.35rem;display:inline-flex;align-items:center}
 .page-heading{margin-bottom:1rem}
 .text-muted{color:#888;font-size:.88rem}
 .cell-sub{display:block;color:#777;font-size:.82rem}   /* second line in a table cell, e.g. the time under a date */
 /* Detail pages: label / value rows (like the customer receipt), an optional shaded side box, and the page's buttons */
 .details{margin:0 0 .5rem;max-width:44rem}
 .details > div{display:grid;grid-template-columns:11rem minmax(0,1fr);gap:.75rem;align-items:baseline;padding:.55rem 0;border-bottom:1px solid #e4e9f0}
 .details > div:last-child{border-bottom:0}
 .details dt{color:#555} .details dd{margin:0;overflow-wrap:anywhere}
 .details-layout{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(0,1fr);gap:1.5rem;align-items:start;margin-bottom:1.2rem}
 .details-layout .details{max-width:none;margin:0}
 .details-aside{padding:1rem;border-radius:10px;background:#eef0f3;overflow-wrap:anywhere} .details-aside h3{margin:0 0 .4rem;font-size:1rem} .details-aside p{margin:0}
 .details-actions{display:flex;flex-wrap:wrap;align-items:center;gap:.6rem;margin:1rem 0}
 .details-actions form{margin:0}
 @media (max-width:700px){.details-layout{grid-template-columns:1fr} .details > div{grid-template-columns:minmax(0,1fr)}}
 /* Daily attendance: time and overtime boxes centered under their titles */
 .attendance-table td:not(:first-child){text-align:center} .attendance-table input{text-align:center}
 /* Roles form: permission checkboxes grouped by area */
 .permission-groups{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem}
 .permission-group{margin:0;padding:.8rem 1rem 1rem;border:1px solid #e3e8ef;border-radius:8px}
 .permission-group legend{padding:0 .4rem;font-weight:700;color:#1f3a5f}
 .check{display:flex;align-items:center;gap:.5rem;margin:.45rem 0 0;font-size:.92rem;cursor:pointer}
 .check input{width:1rem;height:1rem;margin:0;accent-color:#1f3a5f}
 /* Roles list: the Access count opens a pop-up listing what the role can open */
 .access-link{color:#1f3a5f;font-weight:600;text-decoration:underline;text-underline-offset:3px;cursor:pointer}
 .access-dialog{width:min(620px,calc(100% - 2rem));max-height:85vh;box-sizing:border-box;padding:1.4rem 1.6rem;border:0;border-radius:12px;box-shadow:0 20px 50px -12px #0f1f3366}
 .access-dialog::backdrop{background:#0f1f3366}
 .access-dialog h3{margin:0 0 .3rem} .access-dialog > p{margin:0 0 .6rem;color:#555}
 .access-dialog h4{margin:.9rem 0 .25rem;color:#1f3a5f;font-size:.85rem;text-transform:uppercase;letter-spacing:.06em}
 .access-list{margin:0;padding-left:1.2rem;line-height:1.6}
 .access-groups{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 1.5rem} .access-groups h4:first-child{margin-top:.6rem}
 @media (max-width:560px){.access-groups{grid-template-columns:1fr}}
 .access-dialog h3:focus{outline:none}
 .access-dialog .actions{margin-top:1.2rem;justify-content:flex-end}
 .access-dialog .actions .btn,.access-dialog .actions button{min-width:5.5rem;height:2.1rem;display:inline-flex;align-items:center;justify-content:center}
 /* Pop-up forms for small edits (components/modal) */
 .modal-dialog{width:min(440px,calc(100% - 2rem));max-height:85vh;box-sizing:border-box;padding:1.4rem 1.6rem;border:0;border-radius:12px;box-shadow:0 20px 50px -12px #0f1f3366}
 .modal-dialog::backdrop{background:#0f1f3366}
 .modal-dialog h3{margin:0 0 1rem} .modal-dialog h3:focus{outline:none}
 .modal-dialog .field input{box-sizing:border-box;width:100%;height:2.35rem;margin:0}
 .modal-dialog form > .actions{margin-top:1.2rem;justify-content:flex-end}
 .modal-dialog .actions .btn,.modal-dialog .actions button{min-width:5.5rem;height:2.1rem;display:inline-flex;align-items:center;justify-content:center}
 .row{display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:.8rem}
 /* Two cards side by side (e.g. Edit account: details + reset password); stacked on small screens */
 .split{display:grid;grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:1rem;align-items:start} .split > .card{margin-bottom:0}
 @media (max-width:900px){.split{grid-template-columns:1fr}}
 /* Page header (outside the cards): Back link, bigger title, page buttons on the right */
 .back-link{display:inline-block;margin:0 0 .4rem;color:#555;text-decoration:none;font-size:.9rem;font-weight:600}
 .back-link:hover{text-decoration:underline}
 .page-head{display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin:0 0 1rem}
 .page-head h1{margin:0;font-size:2rem;line-height:1.15;font-weight:750;letter-spacing:-.02em}
 .page-sub{margin:-.6rem 0 1rem;color:#555}
 .page-actions{display:flex;gap:.5rem;flex-wrap:wrap}
 .form-grid.single{grid-template-columns:1fr}
 /* .split.even: both cards the same height, each card's buttons pinned to its bottom edge */
 .split.even{align-items:stretch} .split.even > .card{display:flex;flex-direction:column} .split.even > .card > form{display:flex;flex-direction:column;flex:1} .split.even > .card > form > .actions{margin-top:auto;padding-top:1rem}
 .field-label{display:block;margin:0 0 .25rem;font-size:.9rem} .field-value{min-height:2.35rem;display:flex;align-items:center;font-weight:600;overflow-wrap:anywhere}
 .actions .btn,.actions button{min-width:5.5rem}
 nav.staff-nav{scrollbar-width:thin;scrollbar-color:#ffffff40 transparent}   /* long menus: a quiet scrollbar that matches the sidebar */
 .btn.muted{background:#fff;color:#1f3a5f;border:1px solid #1f3a5f}   /* the tab button that is not selected (stock monitoring page) */
 /* Tables everywhere: titles centered and capitalized, content left-aligned */
 main th{text-align:center;text-transform:capitalize}
 main td{text-align:left;vertical-align:middle}
 /* Full-width tables whose columns still read as narrow: the script at the bottom of this layout puts each cell's
    content in an invisible box as wide as the widest entry in its column, centered under the column title. */
 main table.fit td{text-align:center} main table.fit td > .cell{display:inline-block;text-align:left}
 main table.fit td[colspan]{text-align:left}
 /* Buttons inside table cells: all the same size */
 main td .btn,main td button:not([data-step]){box-sizing:border-box;min-width:5.5rem;height:2.1rem;padding:0 .8rem;display:inline-flex;align-items:center;justify-content:center;font-size:.9rem;line-height:1;vertical-align:middle;margin:.1rem .2rem .1rem 0}
 main td form.inline{display:inline-block}
 main td .actions .btn,main td .actions button{margin:0}
 main td .actions{flex-wrap:nowrap}   /* a row's buttons stay side by side */
 /* Filter rows: label above each box, every box and button the same height */
 .report-filter > label,.report-filter > .field{display:flex;flex-direction:column;gap:.25rem;margin:0;font-size:.9rem}
 .report-filter input:not([type=checkbox]):not([type=radio]),.report-filter select{box-sizing:border-box;height:2.35rem;margin:0}
 .report-filter > button,.report-filter > .btn,.report-filter > a.btn{box-sizing:border-box;height:2.35rem;display:inline-flex;align-items:center}
 /* Export Center: full width, filters left-aligned, Download buttons centered */
 .export-table{width:100%} .export-table td:last-child{text-align:center}
 .export-table .report-filter{margin:0;justify-content:flex-start} .export-table td:last-child{white-space:nowrap}
 /* Filters and search bars are not data entry: no required asterisks there */
 form[method="GET" i] .required:after{content:none}
 /* Auth pages (log in, sign up, password): card centered in the viewport on a soft gradient */
 body:has(.card.auth){min-height:100vh;display:flex;flex-direction:column;background:radial-gradient(1100px 560px at 12% -8%,#d9e5f6 0%,transparent 62%),radial-gradient(900px 520px at 108% 108%,#e3eaf4 0%,transparent 58%),#f3f5f9}
 body:not(.staff-layout):has(.card.auth) main{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;width:100%;max-width:none;box-sizing:border-box;margin:0;padding:2.5rem 1rem}
 body:has(.card.auth) main > .alert{width:100%;max-width:440px;box-sizing:border-box}
 .card.auth{position:relative;overflow:hidden;width:100%;max-width:440px;box-sizing:border-box;margin:0 auto;padding:2.4rem 2.4rem 2rem;border:1px solid #e3e8ef;border-radius:18px;box-shadow:0 1px 2px #0f1f330a,0 12px 32px -10px #0f1f3326,0 36px 72px -30px #0f1f3330}
 .card.auth:before{content:"";position:absolute;inset:0 0 auto;height:3px;background:linear-gradient(90deg,#1f3a5f,#5b86bd,#1f3a5f)}
 .auth h2{margin:0 0 .35rem;color:#0f1f33;font-size:1.65rem;font-weight:700;letter-spacing:-.02em}
 .auth-sub{margin:0 0 1.7rem;color:#5b6b80;font-size:.95rem;line-height:1.5}
 .auth input:not([type=checkbox]):not([type=radio]):not([type=hidden]){height:2.8rem;padding:0 .9rem;border:1px solid #d5dce6;border-radius:10px;background:#fbfcfe;font-size:.95rem;color:#0f1f33;transition:border-color .15s ease,box-shadow .15s ease,background .15s ease}
 .auth input:not([type=checkbox]):not([type=radio]):not([type=hidden]):focus{outline:none;border-color:#1f3a5f;background:#fff;box-shadow:0 0 0 4px #1f3a5f1f}
 .auth .password-wrap input:not([type=checkbox]):not([type=radio]):not([type=hidden]){padding-right:2.7rem}
 .auth label{color:#344256;font-size:.85rem;font-weight:600}
 .auth .form-grid{grid-template-columns:1fr;gap:1.05rem}
 .auth form > p{margin:1.4rem 0 0}
 .auth form > p > button{width:100%;height:2.9rem;padding:0 1rem;border-radius:10px;background:linear-gradient(180deg,#25466d,#1a3355);font-size:.95rem;font-weight:600;letter-spacing:.01em;box-shadow:inset 0 1px 0 #ffffff26,0 10px 22px -10px #1f3a5fcc;transition:transform .15s ease,box-shadow .15s ease,filter .15s ease}
 .auth form > p > button:hover{filter:brightness(1.08);transform:translateY(-1px);box-shadow:inset 0 1px 0 #ffffff26,0 14px 26px -10px #1f3a5fcc}
 .auth form > p > button.btn-alt{background:#fff;color:#1f3a5f;border:1px solid #cfd8e4;box-shadow:none}
 .auth-back{display:inline-block;margin-bottom:.8rem;color:#1f3a5f;text-decoration:none;font-size:.88rem;font-weight:600}
 .auth-back:hover{text-decoration:underline}
 .auth-links{margin:1.6rem 0 0;padding-top:1.3rem;border-top:1px solid #edf0f5;font-size:.9rem;color:#5b6b80;display:grid;gap:.45rem;text-align:center}
 .auth-links a{color:#1f3a5f;font-weight:600;text-decoration:none} .auth-links a:hover{text-decoration:underline}
 .choice-label{margin:0 0 .7rem;color:#7a889b;font-weight:700;font-size:.72rem;letter-spacing:.12em;text-transform:uppercase}
 .choice-list{display:grid;gap:.65rem}
 .choice-list a{display:flex;align-items:center;gap:.9rem;padding:.95rem 1rem;border:1px solid #dfe5ed;border-radius:12px;color:#0f1f33;text-decoration:none;background:#fff;transition:border-color .15s ease,background .15s ease,box-shadow .15s ease,transform .15s ease}
 .choice-list a:hover{border-color:#1f3a5f66;background:#f8fafd;box-shadow:0 8px 20px -10px #1f3a5f55;transform:translateY(-1px)}
 .choice-list a:focus-visible{outline:none;border-color:#1f3a5f;box-shadow:0 0 0 4px #1f3a5f1f}
 .choice-icon{flex:none;display:grid;place-items:center;width:42px;height:42px;border-radius:11px;background:#eef3fa;color:#1f3a5f}
 .choice-icon svg,.choice-arrow svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
 .choice-text{flex:1;display:grid;gap:.15rem} .choice-text strong{font-size:.98rem;font-weight:650} .choice-text small{color:#67768a;font-size:.82rem}
 .choice-arrow{color:#9aa8b9;transition:transform .15s ease,color .15s ease}
 .choice-list a:hover .choice-arrow{color:#1f3a5f;transform:translateX(3px)}
 @media (max-width:520px){.card.auth{padding:1.8rem 1.4rem 1.5rem;border-radius:16px}}
 /* Top bar for guests and customers: navy, sticky, current page highlighted */
 nav.top-nav{position:sticky;top:0;z-index:30;gap:.3rem;padding:.8rem 2rem;background:#1f3a5f;box-shadow:0 6px 24px -14px #0f1f3399;color:#fff}
 nav.top-nav > strong{margin-right:1.4rem;color:#fff;font-size:1.08rem;font-weight:800;letter-spacing:.06em}
 nav.top-nav > a{padding:.45rem .8rem;border-radius:8px;color:#d9e4f1;font-size:.92rem;font-weight:500;transition:background .15s ease,color .15s ease}
 nav.top-nav > a:hover{background:#ffffff14;color:#fff}
 nav.top-nav > a.nav-link-active{background:#ffffff1f;color:#fff;font-weight:600}
 nav.top-nav > span{color:#d9e4f1;font-size:.9rem}
 nav.top-nav > a.nav-cta{margin-left:.3rem;padding:.5rem 1.05rem;border-radius:9px;background:#fff;color:#1f3a5f;font-weight:600;box-shadow:0 6px 16px -8px #0006}
 nav.top-nav > a.nav-cta:hover{background:#dceafe;color:#19395e}
 nav.top-nav form button{margin-left:.4rem;padding:.45rem .95rem;border:1px solid #ffffff33;border-radius:9px;background:#ffffff12;color:#fff;font-size:.9rem;font-weight:600;transition:background .15s ease}
 nav.top-nav form button:hover{background:#ffffff24}
 body:has(.card.auth) nav.top-nav .nav-guest-auth{display:none}   /* the auth card already offers log in / sign up */
 @media (max-width:520px){nav.top-nav{padding:.7rem 1rem} nav.top-nav > strong{margin-right:.4rem} nav.top-nav > a{padding:.4rem .6rem}}
 /* Background cart adds (product page): confirmation toast */
 [data-cart-add] button.added{background:#1e6b34}
 .cart-add-error{flex-basis:100%;margin:0;color:#b42318;font-size:.82rem}
 .cart-toast{position:fixed;z-index:50;left:50%;bottom:1.5rem;transform:translate(-50%,1rem);max-width:calc(100% - 2rem);box-sizing:border-box;padding:.7rem 1.1rem;border-radius:10px;background:#0f1f33;color:#fff;font-size:.92rem;box-shadow:0 12px 30px -10px #0f1f3399;opacity:0;pointer-events:none;transition:opacity .2s ease,transform .2s ease}
 .cart-toast.show{opacity:1;transform:translate(-50%,0);pointer-events:auto}
 .cart-toast a{color:#9fc3f0;margin-left:.6rem;font-weight:600}
 .field-hint{margin-top:.25rem;color:#777;font-size:.82rem}
 .password-wrap{position:relative} .password-wrap input{padding-right:2.4rem}
 .password-toggle{position:absolute;top:0;right:0;height:100%;width:2.4rem;padding:0;background:transparent;border:0;color:#666;display:flex;align-items:center;justify-content:center}
 .password-toggle:hover{color:#1f3a5f} .password-toggle svg{width:1.1rem;height:1.1rem;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
 .password-toggle .eye-off,.password-toggle.shown .eye{display:none} .password-toggle.shown .eye-off{display:block}
 /* Storefront (Product Catalog Module) */
 .search{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center} .search input{flex:1;min-width:180px}
 .cats a{display:inline-block;margin:.2rem .4rem .2rem 0;padding:.2rem .6rem;border:1px solid #1f3a5f;border-radius:999px;color:#1f3a5f;text-decoration:none;font-size:.85rem}
 .product{display:flex;flex-direction:column;gap:.25rem;color:inherit;text-decoration:none;margin:0;overflow:hidden;transition:box-shadow .18s ease,transform .18s ease}
 .product:hover{box-shadow:0 8px 20px #0002;transform:translateY(-3px)}
 .product img,.product .noimg{width:100%;height:150px;object-fit:cover;border-radius:5px}
 .product img{transition:transform .25s ease}
 .product:hover img{transform:scale(1.05)}
 .noimg{background:#eef0f3;color:#888;display:flex;align-items:center;justify-content:center;font-size:.85rem}
 .product small,.detail small{color:#666}
 .stock{font-size:.8rem;font-weight:600;padding:.1rem .5rem;border-radius:999px;align-self:flex-start}
 .stock.in{background:#e6f4ea;color:#1e6b34} .stock.out{background:#fdecea;color:#8a1f17} .stock.low{background:#fff5d8;color:#785b00}
 .brand{margin:.2rem 0;color:#555}
 /* Storefront on phones: two product columns, filters stacked full width, wide tables scroll sideways */
 @media (max-width:520px){
  .grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:.6rem}
  .product img,.product .noimg{height:110px}
  .search > *{flex:1 1 100%} .search input{min-width:0}
  .cart-table{display:block;overflow-x:auto}
 }
 /* Order status badges (orders/_status) */
 .grid-checkout { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
  @media(min-width: 900px) { .grid-checkout { grid-template-columns: 2fr 1fr; } }
  
  .cart-table-modern { width: 100%; border-collapse: collapse; margin-top: 1rem; }
  .cart-table-modern th { text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb; font-size: 0.85rem; color: #4b5563; text-transform: uppercase; }
  .cart-table-modern td { padding: 1rem 0.75rem; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
  
  .checkout-card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
  .checkout-summary-box { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.25rem; position: sticky; top: 1.5rem; }
  
  .btn-primary { background: #1f3a5f; color: #ffffff; border: none; padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; }
  .btn-primary:hover { background: #172d49; }
  .btn-danger-sm { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; padding: 0.35rem 0.65rem; border-radius: 6px; font-size: 0.8rem; cursor: pointer; }
  .btn-danger-sm:hover { background: #fecaca; }

  .payment-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; margin-top: 0.5rem; }
  .payment-option { border: 1.5px solid #d1d5db; border-radius: 8px; padding: 0.85rem; display: flex; flex-direction: column; cursor: pointer; }
  .payment-option input:checked + div { font-weight: bold; color: #1f3a5f; }
 .status{display:inline-block;font-size:.8rem;font-weight:600;padding:.1rem .55rem;border-radius:999px;background:#eef0f3;color:#333}
 .status-pending{background:#fff5d8;color:#785b00} .status-completed,.status-delivered{background:#e6f4ea;color:#1e6b34} .status-cancelled{background:#fdecea;color:#8a1f17}
 .detail{display:grid;grid-template-columns:minmax(0,320px) 1fr;gap:1.2rem} .detail img,.detail .noimg{width:100%;height:280px;object-fit:cover;border-radius:5px}
 .detail h2{margin:.2rem 0} .price{font-size:1.3rem;font-weight:600;margin:.3rem 0}
 @media (max-width:600px){.detail{grid-template-columns:1fr}}
 .pager{display:flex;gap:1rem;justify-content:center;align-items:center} .pager span{color:#888}
 /* Cart */
 .qty{display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;margin:0} .qty label{margin:0}
 .cart-table th,.cart-table td{text-align:center;vertical-align:middle} .cart-table .qty{justify-content:center}
 .cart-table tr > :first-child{text-align:left}   /* Product column stays left-aligned */
 .cart-table .cart-total-row th:first-child{text-align:right}   /* "Total" label sits next to the amount */
 .report-heading{display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}
 .report-heading h2{margin:.2rem 0}
 .report-filter{display:flex;align-items:end;gap:.8rem;flex-wrap:wrap;margin:1rem 0}
 .report-filter label{margin:0}
 .report-summary{display:flex;gap:1rem;flex-wrap:wrap;margin:1rem 0}
 .report-summary > div{background:#f4f5f7;border-radius:5px;padding:.7rem 1rem;min-width:11rem;display:grid;gap:.3rem}
 .stepper{display:inline-flex;align-items:stretch;border:1px solid #ccc;border-radius:8px;overflow:hidden;background:#fff}
 .stepper button{background:#eef0f3;color:#1f3a5f;border-radius:0;padding:.3rem .7rem;font-size:1rem;font-weight:600}
 .stepper button:hover{background:#dde2e8}
 .stepper input{width:3.5rem;margin:0;border:0;border-radius:0;border-left:1px solid #ccc;border-right:1px solid #ccc;text-align:center;-moz-appearance:textfield}
 .stepper input::-webkit-outer-spin-button,.stepper input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
 /* Storefront header: one row (logo, Home, Products, search, account links) spread toward the screen edges */
 body.storefront main{max-width:1240px}
 .sf-head{background:#fff;box-shadow:0 1px 3px #0001}
 .sf-head-in{padding:.75rem clamp(1.25rem,2.5vw,3rem);display:flex;align-items:center;gap:clamp(.8rem,2vw,1.6rem);flex-wrap:wrap}
 .sf-logo{display:flex;flex-direction:column;color:#1f3a5f;text-decoration:none;line-height:1.05;flex-shrink:0}
 .sf-logo strong{font-size:1.45rem;letter-spacing:.05em}
 .sf-logo span{font-size:.64rem;color:#f2b705;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
 .sf-links{display:flex;gap:.25rem}
 .storefront .sf-links a{display:block;padding:.5rem .65rem;color:#22303f;text-decoration:none;font-size:.92rem;font-weight:600;white-space:nowrap;border-bottom:2px solid transparent}
 .storefront .sf-links a:hover{color:#1f3a5f}
 .storefront .sf-links a.active{color:#1f3a5f;border-bottom-color:#f2b705}
 .sf-search{flex:1;display:flex;gap:.5rem;min-width:200px;max-width:640px}
 .sf-search input{flex:1;min-width:0;margin:0;font-size:.95rem}
 .sf-search button{padding:.6rem 1.3rem;font-weight:600;white-space:nowrap}
 .sf-actions{display:flex;align-items:center;gap:.9rem;margin-left:auto}
 .storefront .sf-actions > a{color:#22303f;text-decoration:none;font-size:.88rem;font-weight:600}
 .storefront .sf-actions > a:hover{color:#1f3a5f}
 .sf-logout{display:inline-flex;align-items:center;margin:0}
 .storefront .sf-logout button{background:none;border:0;padding:0;min-height:0;line-height:inherit;font-family:inherit;color:#22303f;font-size:.88rem;font-weight:600;cursor:pointer}
 .storefront .sf-logout button:hover{color:#1f3a5f;background:none}
 .sf-cart{position:relative;display:inline-flex;align-items:center;gap:.3rem}
 .sf-cart b{display:inline-grid;place-items:center;min-width:1.25rem;height:1.25rem;padding:0 .3rem;border-radius:999px;background:#f2b705;color:#1a1a1a;font-size:.72rem}
 /* Footer: light text on navy. ".storefront" in front so storefront.css's navy link color does not win. */
 .sf-footer{background:#172d49;color:#dbe4ee;margin-top:2.5rem}
 .sf-footer-in{padding:2rem clamp(1.25rem,2.5vw,3rem);display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1.5rem}
 .sf-footer h4{color:#fff;margin:0 0 .6rem;font-size:.8rem;letter-spacing:.07em;text-transform:uppercase}
 .storefront .sf-footer a{color:#dbe4ee;text-decoration:none;display:block;padding:.15rem 0;font-size:.88rem}
 .storefront .sf-footer a:hover{color:#fff;text-decoration:underline}
 .sf-footer-note{display:block;padding:.15rem 0;font-size:.85rem;color:#c3d0de}
 .sf-footer-base{border-top:1px solid #ffffff22;padding:.8rem 1.2rem;text-align:center;font-size:.78rem;color:#c3d0de}
 /* Shop page: Ace-style filter sidebar + product grid */
 .shop{display:grid;grid-template-columns:230px minmax(0,1fr);gap:1.2rem;align-items:start}
 .shop-side .card{padding:.9rem 1rem}
 .shop-side h3,.shop-side h4{margin:.15rem 0 .55rem}
 .shop-side form label{margin-top:.45rem}
 .shop-side form button{margin-top:.6rem}
 .shop-count{margin:.2rem 0 .8rem;color:#555;font-size:.9rem}
 @media (max-width:800px){.shop{grid-template-columns:1fr}}
 .grid .card.product{margin-bottom:0}
 .product-media{display:block}
 .product a{color:inherit;text-decoration:none}
 .product .product-name{display:block;margin-top:.15rem}
 .product .product-name:hover strong{text-decoration:underline}
 .product-foot{display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-top:.2rem}
 .product .price{font-size:1.05rem;font-weight:700;margin:0}
 .product-add{width:100%;margin-top:.55rem;padding:.45rem .6rem;font-size:.88rem}
 @media (max-width:520px){.sf-logo strong{font-size:1.15rem}.sf-head-in{padding:.6rem .8rem}}
 .status { display: inline-block; padding: 0.25rem 0.65rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.status-pending { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
.status-completed { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.status-cancelled { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

.filter-bar { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; background: #ffffff; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 10px; }
.filter-bar select { padding: 0.5rem 0.85rem; border-radius: 6px; border: 1px solid #d1d5db; font-size: 0.9rem; }

/* General product card media rules for grid stability across catalog views */
.product-media { display: block; width: 100%; height: 160px; overflow: hidden; background: #f8fafc; }
.product-media img, .product img { width: 100%; height: 160px; object-fit: cover; display: block; }
</style>
@if(! auth('web')->check() && request()->routeIs('home', 'catalog.*', 'customer.*', 'login', 'register', 'staff.register', 'password.*'))
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}">
@endif
@stack('head')
</head>
<body class="@auth('web') staff-layout @else storefront @endauth">
@auth('web')
<nav class="staff-nav">
    <strong>IMS-HE</strong>

        @php
            $user = auth()->user();

            // Each link shows when the account's role has the permission its page needs (see the Roles page).
            $isAdministrator = $user->hasRole(\App\Models\User::ADMINISTRATOR);
            $can = fn (string ...$permissions) => $user->hasPermission(...$permissions);
        @endphp

        {{-- WORKSPACE --}}
        <span class="nav-section">Workspace</span>

        @unless($user->isPending())
        <a class="{{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}"
           href="{{ route('dashboard') }}">
            Dashboard
        </a>
        @endunless

        {{-- ONLINE ORDERS (task J4) --}}
        @if($can('online_orders.manage'))
            <a class="{{ request()->routeIs('online-orders.*') ? 'nav-link-active' : '' }}"
               href="{{ route('online-orders.index') }}">
                Online Orders
            </a>
        @endif

        @if($can('sales.pos'))
            <a class="{{ request()->routeIs('sales.*') ? 'nav-link-active' : '' }}"
               href="{{ route('sales.create') }}">
                Sales (POS)
            </a>
        @endif

        @if($can('attendance.manage'))
            <a class="{{ request()->routeIs('attendance.*') ? 'nav-link-active' : '' }}"
               href="{{ route('attendance.day') }}">Attendance</a>
        @elseif($can('attendance.own'))
            <a class="{{ request()->routeIs('attendance.*') ? 'nav-link-active' : '' }}"
               href="{{ route('attendance.my') }}">Attendance</a>
        @endif

        @if($can('payroll.view'))
            <a class="{{ request()->routeIs('payrolls.*') ? 'nav-link-active' : '' }}"
               href="{{ route('payrolls.index') }}">Payroll</a>
        @endif

        @if($can('customers.manage'))
            <a class="{{ request()->routeIs('customers.*') ? 'nav-link-active' : '' }}"
               href="{{ route('customers.index') }}">Customers</a>
        @endif

        {{-- INVENTORY --}}
        @if($can('inventory.manage'))
            <details class="nav-dropdown"
                {{ request()->routeIs('products.*', 'categories.*', 'stock.*') ? 'open' : '' }}>
                <summary>Inventory</summary>
                <div>
                    <a class="{{ request()->routeIs('products.*') ? 'nav-link-active' : '' }}"
                       href="{{ route('products.index') }}">
                        Products
                    </a>

                    <a class="{{ request()->routeIs('categories.*') ? 'nav-link-active' : '' }}"
                       href="{{ route('categories.index') }}">
                        Categories
                    </a>

                    <a class="{{ request()->routeIs('stock.*') ? 'nav-link-active' : '' }}"
                       href="{{ route('stock.index') }}">
                        Stock
                    </a>
                </div>
            </details>
        @endif

        {{-- OPERATIONS --}}
        @if($can('suppliers.manage', 'procurement.manage'))
            <span class="nav-section">Operations</span>

            @if($can('suppliers.manage'))
            <a class="{{ request()->routeIs('suppliers.*') ? 'nav-link-active' : '' }}"
               href="{{ route('suppliers.index') }}">
                Suppliers
            </a>
            @endif

            @if($can('procurement.manage'))
            <a class="{{ request()->routeIs('purchase-orders.*') ? 'nav-link-active' : '' }}"
               href="{{ route('purchase-orders.index') }}">
                Purchase Orders
            </a>

            <a class="{{ request()->routeIs('deliveries.*') ? 'nav-link-active' : '' }}"
               href="{{ route('deliveries.create') }}">
                Receive Delivery
            </a>
            @endif
        @endif

        {{-- REPORTS --}}
        @if($can('reports.sales', 'reports.inventory', 'reports.procurement', 'reports.financial'))
            <details class="nav-dropdown"
                {{ request()->routeIs('reports.*') ? 'open' : '' }}>
                <summary>Reports</summary>
                <div>
                    @if($can('reports.sales'))
                        <a class="{{ request()->routeIs('reports.sales*') ? 'nav-link-active' : '' }}"
                           href="{{ route('reports.sales') }}">
                            Sales Report
                        </a>
                    @endif

                    @if($can('reports.inventory'))
                        <a class="{{ request()->routeIs('reports.inventory*') ? 'nav-link-active' : '' }}"
                           href="{{ route('reports.inventory') }}">
                            Inventory Report
                        </a>
                    @endif

                    @if($can('reports.procurement'))
                        <a class="{{ request()->routeIs('reports.procurement*') ? 'nav-link-active' : '' }}"
                           href="{{ route('reports.procurement') }}">
                            Procurement Report
                        </a>
                    @endif

                    @if($can('reports.financial'))
                        <a class="{{ request()->routeIs('reports.financial*') ? 'nav-link-active' : '' }}"
                           href="{{ route('reports.financial') }}">Financial Reports</a>
                    @endif
                </div>
            </details>
        @endif

        @if($can('ledger.view'))
            <a class="{{ request()->routeIs('ledger.*') ? 'nav-link-active' : '' }}"
               href="{{ route('ledger.index') }}">Ledger</a>
        @endif

        @if($can('employees.manage'))
            <a class="{{ request()->routeIs('employees.*') ? 'nav-link-active' : '' }}"
               href="{{ route('employees.index') }}">Employees</a>
        @endif

        {{-- ADMINISTRATION --}}
        @if($isAdministrator)
            <span class="nav-section">Administration</span>

            <a class="{{ request()->routeIs('users.*') ? 'nav-link-active' : '' }}"
               href="{{ route('users.index') }}">
                Employee Accounts
            </a>

            <a class="{{ request()->routeIs('customer-accounts.*') ? 'nav-link-active' : '' }}"
               href="{{ route('customer-accounts.index') }}">
                Customer Accounts
            </a>

            <a class="{{ request()->routeIs('roles.*') ? 'nav-link-active' : '' }}"
               href="{{ route('roles.index') }}">
                Roles
            </a>

            <a class="{{ request()->routeIs('audit.*') ? 'nav-link-active' : '' }}"
               href="{{ route('audit.index') }}">
                Audit Log
            </a>
        @endif

        @if($can('exports.inventory', 'exports.suppliers', 'exports.sales', 'exports.payroll', 'exports.customers'))
            <a class="{{ request()->routeIs('exports.*') ? 'nav-link-active' : '' }}"
               href="{{ route('exports.index') }}">
                Export Center
            </a>
        @endif

        {{-- USER ACCOUNT AND LOGOUT --}}
        <span class="nav-sp"></span>

        <div class="nav-user">
            <strong>{{ $user->displayName() }}</strong>
            <span>{{ $user->role }}</span>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>

</nav>
@else
<header class="sf">
    <div class="sf-head">
        <div class="sf-head-in">
            <a class="sf-logo" href="{{ route('home') }}">
                <strong>IMS-HE</strong>
                <span>Ibay Enterprises</span>
            </a>
            <div class="sf-links">
                <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')])>Home</a>
                <a href="{{ route('catalog.index') }}" @class(['active' => request()->routeIs('catalog.*')])>Products</a>
            </div>
            <form class="sf-search" method="GET" action="{{ route('catalog.index') }}">
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="Search tools, paint, plumbing &amp; more"
                       maxlength="100" aria-label="Search products">
                <button type="submit">Search</button>
            </form>
            <div class="sf-actions">
                {{-- The cart needs a customer login, so guests only see Sign In / Sign Up. --}}
                @auth('customer')
                    <a class="sf-cart" href="{{ route('customer.cart.index') }}">
                        Cart
                        <b id="cart-count">{{ auth('customer')->user()->cart?->items()->sum('quantity') ?? 0 }}</b>
                    </a>
                    <a href="{{ route('customer.orders.index') }}">My Orders</a>
                    <a href="{{ route('customer.account') }}">My Account</a>
                    <form method="POST" action="{{ route('customer.logout') }}" class="sf-logout">@csrf
                        <button type="submit">Log Out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Sign In</a>
                    <a href="{{ route('register') }}">Sign Up</a>
                @endauth
            </div>
        </div>
    </div>
</header>
@endif

@yield('hero')
<main>
 {{-- Page header: Back link (every page except the main ones), then the page title outside the cards,
      with the page's own buttons (@section('page-actions')) on the same row. --}}
 @php
  $isStartPage = request()->routeIs('dashboard', 'home', 'login', 'register', 'customer.register', 'staff.register', 'password.*', 'staff.pending');
  $backFallback = auth('web')->check() ? route('dashboard') : route('home');
  $backUrl = url()->previous() !== url()->current() ? url()->previous() : $backFallback;
 @endphp
 @unless($isStartPage)
  <a class="back-link no-print" href="{{ $backUrl }}" onclick="if (history.length > 1 && document.referrer.indexOf(location.host) !== -1) { history.back(); return false; }">Back</a>
 @endunless
 @hasSection('heading')
  <div class="page-head">
   <h1>@yield('heading')</h1>
   @hasSection('page-actions')<div class="page-actions no-print">@yield('page-actions')</div>@endif
  </div>
 @endif
 @if(session('status'))<x-alert type="success" title="Success">{{ session('status') }}</x-alert>@endif
 @if(session('warning'))<x-alert type="warning" title="Warning">{{ session('warning') }}</x-alert>@endif
 @if($errors->any())
  <x-alert type="error" title="Please correct the following">
   <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </x-alert>
 @endif
 @yield('content')
</main>
@auth('web')@else
<footer class="sf-footer">
    <div class="sf-footer-in">
        <div>
            <h4>Shop</h4>
            <a href="{{ route('catalog.index') }}">All Products</a>
        </div>
        <div>
            <h4>My Account</h4>
            @auth('customer')
                <a href="{{ route('customer.account') }}">Account Settings</a>
                <a href="{{ route('customer.orders.index') }}">My Orders</a>
                <a href="{{ route('customer.cart.index') }}">Cart</a>
            @else
                <a href="{{ route('login') }}">Sign In</a>
                <a href="{{ route('register') }}">Create Account</a>
            @endauth
        </div>
        <div>
            <h4>Ibay Enterprises</h4>
            <a href="{{ route('home') }}">Home</a>
            <span class="sf-footer-note">Tools, plumbing, electrical and building supplies.</span>
        </div>
    </div>
    <div class="sf-footer-base">&copy; {{ date('Y') }} Ibay Enterprises Corporation. All rights reserved.</div>
</footer>
@endif
<script>
 // Quantity steppers (partials/qty): - / + stay within the input's min and max (available stock).
 document.addEventListener('click', function (e) {
  var btn = e.target.closest('.stepper [data-step]');
  if (!btn) return;
  var input = btn.parentElement.querySelector('input');
  var min = Number(input.min) || 1, max = Number(input.max) || Infinity;
  var next = (Number(input.value) || min) + Number(btn.dataset.step);
  input.value = Math.min(max, Math.max(min, next));
  input.dispatchEvent(new Event('input', { bubbles: true }));   // lets pages react, e.g. the cart's auto-update
 });

 // Cart forms marked data-cart-add submit in the background, so the shopper stays on the page.
 // Success shows a short confirmation with a "View cart" link; guests are sent to the customer login.
 (function () {
  var toast, toastTimer;
  function showToast(message) {
   if (!toast) {
    toast = document.createElement('div');
    toast.className = 'cart-toast';
    toast.setAttribute('role', 'status');
    document.body.appendChild(toast);
   }
   toast.textContent = message;
   var link = document.createElement('a');
   link.href = @json(route('customer.cart.index'));
   link.textContent = 'View cart';
   toast.appendChild(link);
   toast.classList.add('show');
   clearTimeout(toastTimer);
   toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 3000);
  }

  document.addEventListener('submit', function (e) {
   var form = e.target.closest('[data-cart-add]');
   if (!form) return;
   e.preventDefault();
   var button = form.querySelector('button:not([type=button])'), error = form.querySelector('.cart-add-error');
   var label = button.dataset.label || (button.dataset.label = button.textContent);
   button.disabled = true;
   if (error) error.hidden = true;

   fetch(form.action, {
    method: 'POST',
    body: new FormData(form),
    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
   }).then(function (res) {
    if (res.status === 401) { window.location = @json(route('login', ['as' => 'customer'])); return null; }
    return res.json().then(function (data) { return { ok: res.ok, data: data }; });
   }).then(function (r) {
    if (!r) return;
    if (!r.ok) throw new Error(r.data.errors && r.data.errors.quantity ? r.data.errors.quantity[0] : (r.data.message || 'Could not add to cart.'));
    var count = document.getElementById('cart-count');
    if (count) count.textContent = r.data.count;
    showToast(r.data.message);
    button.textContent = 'Added ✓';
    button.classList.add('added');
    setTimeout(function () { button.textContent = label; button.classList.remove('added'); }, 1600);
   }).catch(function (err) {
    if (error) { error.textContent = err.message; error.hidden = false; }
   }).finally(function () { button.disabled = false; });
  });
 })();

 // A pop-up form the server sent back with errors opens again by itself (components/modal).
 document.querySelectorAll('dialog[data-open-on-load]').forEach(function (dialog) { dialog.showModal(); });

 // Tables: each cell's content goes in an invisible box as wide as the widest entry in its column, centered under
 // the column title. The table keeps the full width, but every column reads as narrow and lines up on the left.
 // Skipped: the customer cart (its own layout), tables marked .no-fit, and tables you type into (their inputs need the whole cell).
 (function () {
  function column(td) { var i = 0, c = td; while ((c = c.previousElementSibling)) { i += c.colSpan; } return i; }

  document.querySelectorAll('main table').forEach(function (table) {
   if (table.matches('.cart-table, .no-fit') || table.querySelector('td input:not([type=hidden]):not([type=checkbox]), td textarea')) return;

   var boxes = [], widths = [];
   table.classList.add('fit');
   table.querySelectorAll('td').forEach(function (td) {
    if (td.colSpan > 1 || td.closest('table') !== table) return;
    var box = document.createElement('div');
    box.className = 'cell';
    while (td.firstChild) { box.appendChild(td.firstChild); }
    td.appendChild(box);
    boxes.push(box);
   });
   boxes.forEach(function (box) { var i = column(box.parentElement); widths[i] = Math.max(widths[i] || 0, Math.ceil(box.getBoundingClientRect().width)); });
   boxes.forEach(function (box) { box.style.minWidth = widths[column(box.parentElement)] + 'px'; });
  });
 })();
</script>
@stack('scripts')
</body>
</html>
