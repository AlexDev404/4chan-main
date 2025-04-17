<?php if (!defined('IN_APP')) die() ?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="referrer" content="never">
  <title>Agreement</title>
  <link rel="shortcut icon" href="data:image/x-icon;base64,AAABAAEAEA8AAAEAIAAkBAAAFgAAACgAAAAQAAAAHgAAAAEAIAAAAAAAAAAAABMLAAATCwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/wAAAAAAAAD/AAAAAAAAAAAAAAAAAAAAAAAAAP8AAAAAAAAA/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/zPMZv8AAAD/M8xm/wAAAP8AAAAAAAAAAAAAAP8zzGb/AAAA/zPMZv8AAAD/AAAAAAAAAAAAAAAAAAAAAAAAAP8zzGb/M8xm/wAA//8AAP//AAAAAAAAAAAAAP//AAD//zPMZv8zzGb/AAAA/wAAAAAAAAAAAAAAAAAAAAAAAAD/M8xm/zPMZv8AAP//AAD//wAAAAAAAAAAAAD//wAA//8zzGb/M8xm/wAAAP8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP8zzGb/AAD//wAA//8AAAAAAAAAAAAA//8AAP//M8xm/wAAAP8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/wAA//8AAP//AAD//wAA//8AAP//AAD//wAAAP8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP//AAD//wAAAAAAAAAAAAD//wAA//8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD//wAA//8AAAAAAAAAAAAA//8AAP//AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP8AAAD/AAAA/wAA//8AAP//AAAAAAAAAAAAAP//AAD//wAAAP8AAAD/AAAA/wAAAAAAAAAAAAAAAAAAAP8zzGb/M8xm/zPMZv8zzGb/AAD//wAA//8AAP//AAD//zPMZv8zzGb/M8xm/zPMZv8AAAD/AAAAAAAAAAAAAAAAAAAA/zPMZv8zzGb/M8xm/wAAAP8AAP//AAD//wAAAP8zzGb/M8xm/zPMZv8AAAD/AAAAAAAAAAAAAAAAAAAA/zPMZv8zzGb/M8xm/wAAAP8AAAAAAAAAAAAAAAAAAAAAAAAA/zPMZv8zzGb/M8xm/wAAAP8AAAAAAAAAAAAAAAAAAAD/AAAA/wAAAP8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/AAAA/wAAAP8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD//wAA69cAAMGDAADBgwAAwYMAAOGHAADwDwAA+Z8AAPmfAADBgwAAgAEAAMADAACDwQAAx+MAAP//AAA=">
  <style type="text/css">
body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,code,form,fieldset,legend,input,textarea,p,blockquote,th,td{margin:0;padding:0}table{border-collapse:collapse;border-spacing:0}fieldset,img{border:0}address,caption,cite,code,dfn,em,strong,th,var{font-style:normal;font-weight:normal}ol,ul{list-style:none}caption,th{text-align:left}h1,h2,h3,h4,h5,h6{font-size:100%;font-weight:normal}q:before,q:after{content:''}abbr,acronym{border:0;font-variant:normal}sup{vertical-align:text-top}sub{vertical-align:text-bottom}input,textarea,select{font-family:inherit;font-size:inherit;font-weight:inherit}input,textarea,legend{color:#000}

html {
  overflow-y: scroll;
}

body {
  font-size: 13px;
  font-family: 'Helvetica Neue', arial, sans-serif;
  color: #393836;
  background-color: #E7E7E7;
}

header {
  background-color: #2b2b2b;
  color: #9e9e9e;
  padding: 5px 10px 10px 10px;
  box-shadow: 0 0 0 3px rgba(45, 77, 59, 0.20);
  padding-top: 5px;
  text-align: center;
}

footer {
  margin-top: 10px;
  padding-bottom: 10px;
}

a {
  color: #00A550;
}

select:focus {
  outline: none;
}

h3 {
  font-size: 20px;
  font-weight: bold;
}

h4 {
  font-size: 13px;
}

h5 {
  font-size: 12px;
  font-weight: normal;
}

h4, h5 {
  margin-bottom: 5px;
  font-weight: bold;
}

#sign-field {
  width: 200px;
}

#date-field {
  width: 85px;
}

input[type=text] {
  border: 1px solid #D2D4D3;
  padding: 3px;
}

input[type=text]:focus {
  outline: none;
  border: 1px solid #00a550;
}

input[type=checkbox] {
  margin-right: 5px;
}

#agreement-form label {
  display: block;
  margin: 10px 0;
}

.protip {
  font-size: 11px;
  margin-bottom: 5px;
}

.field {
  margin-bottom: 20px;
}

strong {
  font-weight: bold;
}

#agreement {
  line-height: 1.5em;
}

#agreement p {
  margin-bottom: 20px;
}

#form-header {
  margin: 25px 0;
  padding-top: 25px;
  border-top: 1px solid #d2d4d3;
}

.apt {
  display: block;
  margin-bottom: 5px;
}

.center {
  text-align: center;
}

.larger {
  font-size: larger;
}

#submit-btn {
  display: block;
  margin-top: 50px;
}

#content {
  width: 600px;
  margin: 20px auto;
  border: 1px solid #D2D4D3;
  padding: 50px;
  background: #f2f2f2;
}

.right {
  float: right;
}

#title {
  color: #dedede;
  font-size: 20px;
}

#menu {
  background-color: #d2d4d3;
  padding: 6px 5px 5px 5px;
  text-align: center;
  height: 18px;
}

#menu li {
  display: inline;
}

#menu input {
  background-color: #E7E7E7;
  width: 100px;
  height: 18px;
  -webkit-appearance: none;
  border: 0;
  vertical-align: top;
}

.button {
  cursor: pointer;
  background-color: #2B2B2B;
  border-radius: 5px;
  -moz-user-select: none;
  -webkit-user-select: none;
  -ms-user-select: none;
  user-select: none;
  padding: 2px 4px;
  white-space: nowrap;
  text-decoration: none;
  color: #fff;
  box-shadow: 0 1px 1px rgba(0, 0, 0, 0.15);
  margin: 0 2px;
}

.btn-other {
  background-color: #1d8dc4;
}

.btn-accept {
  background-color: #00A550;
}

.btn-deny {
  background-color: #C41E3A;
}

span.button:focus,
.button:hover {
  color: #fff;
  background-color: #000;
}

.button-light {
  color: inherit;
  background-color: #e7e7e7;
}

.button-light:hover {
  background-color: #00A550;
}

#doc-table {
  margin: 30px auto;
  width: 70%;
}

.items-table {
  width: 100%;
  cursor: default;
  margin: auto;
  border-left: 1px solid #D2D4D3;
  border-right: 1px solid #D2D4D3;
  margin-top: 10px;
}

.items-table th {
  background-color: #D2D4D3;
  font-weight: bold;
  padding: 5px;
  text-align: center;
}

.items-table td {
  border-left: 1px solid #D2D4D3;
  padding: 5px;
  vertical-align: middle;
  text-align: center;
}

.items-table tr {
  border-bottom: 1px solid #D2D4D3;
}

.note {
  color: #b1b3b2;
  font-size: 11px;
  margin-top: 3px;
}

.wm {
  color: #ededed;
  font-size: 10px;
  letter-spacing: 5px;
  margin-top: -15px;
  position: absolute;
  text-decoration: none;
  margin-left: 445px;
}

.wm2 {
  color: #ededed;
  font-size: 10px;
  letter-spacing: 5px;
  margin-top: -15px;
  position: absolute;
  text-decoration: none;
}

/* Tooltips */ 
#tooltip {
  position: absolute;
  background-color: #181f24;
  border-radius: 3px;
  font-size: 12px;
  padding: 5px 8px 4px 8px;
  z-index: 100000;
  word-wrap: break-word;
  white-space: pre-line;
  max-width: 400px;
  color: #dedede;
}

#tooltip s {
  background-color: #000;
  color: #000;
}
  </style>
</head>
<body>
<header>
  <h1 id="title">Agreement</h1>
</header>
<div id="content">
<div id="agreement">
<p class="center larger"><strong>4CHAN COMMUNITY SUPPORT, LLC<br>Volunteer Moderator Agreement</strong></p>
<p>The following are the agreed-upon terms pursuant to which you will be volunteering with 4chan, LLC
("4chan") as a Volunteer Moderator:</p>
<p><strong class="apt">1. Duties.</strong>This is a volunteer position. This letter agreement is executed as a deed. As a Volunteer Moderator, you will monitor user-posted content on 4chan's website at 4chan.org, including its subdomains and associated domains and any message boards contained thereon (collectively, the “Website”), and (a) report, edit, and/or remove user-posted content that is in violation of the Rules posted at http://www.4chan.org/rules (“Prohibited Content”) and (b) block users from accessing the Website for posting Prohibited Content (collectively, the “Moderation Duties”). You will perform the Moderation Duties in accordance with this letter agreement and any instructions and guidelines for Volunteer Moderators provided by 4chan to you from time-to-time. By entering into this letter agreement and performing the Moderation Duties, you represent and warrant that (x) you have no contractual commitments or other legal obligations that would prohibit your activities in connection with 4chan, (y) you will perform the Moderation Duties in a good and professional manner, and (z) you have full power, right and authority to enter into this letter agreement. You may not take any action other than (a) and (b) as specified herein, and you may not take any action which 4chan considers as inappropriate. In case of breach of such prohibitions, 4chan may terminate this Agreement at any time with notice by email. In such case, upon termination, you may not access Website by using ID and Password which 4chan has lent to you hereunder.</p>
<p><strong class="apt">2. Relationship; No Compensation.</strong>You understand and agree that this is an unpaid, volunteer position and not an employment relationship.  This position is for no specific period of time and will be “at will,” meaning that either you or 4chan may terminate your position and the service relationship at any time and for any reason, with or without cause.  Although you must perform the Moderation Duties in accordance with all terms and conditions contained in this letter agreement, or as otherwise provided by 4chan to you, 4chan does not require any specific time commitment for this position (i.e. it is not “full time” or “part time”), and the amount of time you spend performing the Moderation Duties is solely at your own discretion.  You acknowledge and agree that 4chan will not pay you any wages or other compensation (including without limitation any equity or ownership share in 4chan) at any time, now or in the future, under any circumstances, for performing the Moderation Duties, and you will not be eligible to participate in any 4chan company-sponsored benefits.   Any contrary representations that may have been made to you are superseded by this letter agreement.  4chan may terminate your position and the service relationship with or without notice to you.</p>
<p><strong class="apt">3. Proprietary Information.</strong>You acknowledge that in the course of performing the Moderation Duties, you may encounter or otherwise gain access to certain Proprietary Information of 4chan, and you agree to hold in confidence and not disclose to any third parties in any manner or medium whatsoever or, except in performing the Moderation Duties, use any Proprietary Information.  “Proprietary Information” means all financial, business, legal and technical information of 4chan or any of its affiliates, suppliers, customers, users, contractors, owners, and employees (including without limitation information about user-posted content, personally identifiable information pertaining to users (such as but not limited to IP addresses, location, and cookie data), team member identities or nicknames (including without limitation other moderators, and regardless of whether such identities or nicknames are real-life or virtual), chat logs, orientation materials, legal or regulatory affairs, operations, marketing, transactions, inventions, processes, materials, data, know-how and ideas, whether tangible or intangible, and including all copies and other derivatives thereof), that is previously, presently or subsequently disclosed by or for 4chan to you or which is otherwise made available to you or which you learn in the course of performing the Moderation Duties.   For the avoidance of doubt, this letter agreement, its terms, and its existence shall constitute 4chan's Proprietary Information.  Upon termination or as otherwise requested by 4chan, you will promptly return to 4chan all items and copies containing or embodying Proprietary Information.</p>
<p><strong class="apt">4. Survival.</strong>Your obligations under Sections 1, 3, 4 and 5 of this letter agreement shall survive any termination or expiration of this letter agreement.</p>
<p><strong class="apt">5. Miscellaneous.</strong>This letter agreement constitutes the entire agreement, and supersedes all prior negotiations, understandings or agreements (oral or written), between the parties concerning the subject matter hereof. No change, consent or waiver to this letter agreement will be effective unless in writing and signed by the party against which enforcement is sought. The failure of 4chan to enforce its rights under this letter agreement at any time for any period shall not be construed as a waiver of such rights. Unless expressly provided otherwise, each right and remedy in this letter agreement is in addition to any other right or remedy, at law or in equity, and the exercise of one right or remedy will not be deemed a waiver of any other right or remedy.  In the event that any provision of this letter agreement shall be determined to be illegal or unenforceable, that provision will be limited or eliminated to the minimum extent necessary so that letter agreement shall otherwise remain in full force and effect and enforceable.  This letter agreement shall be governed by and construed in accordance with the laws of the State of New York, USA without regard to the conflicts of laws provisions thereof.  Exclusive jurisdiction and venue for any action arising under this letter agreement is in the federal and state courts located in New York City, and both parties hereby consent to such jurisdiction and venue for this purpose.  Any notice hereunder will be effective upon receipt and shall be given in writing, in English and delivered to the other party in a manner indicated by 4chan.</p>
</div>
<div id="form-header">You may indicate your agreement with these terms and accept this offer by signing and dating this letter
agreement. You may also opt-out of providing an electronic signature via this web form, and instead provide a physical signature with pen and paper if you would prefer. Please contact <a href="mailto:legal@4chan.org?subject=4chan%20Volunteer%20Moderator%20Agreement">legal@4chan.org</a> for more information regarding the foregoing.</div>
<form id="agreement-form" action="" method="post" enctype="multipart/form-data">
  <div class="field">
  <h4>Your first name</h4>
  <input type="text" name="first_name" required>
  </div>
  <div class="field">
  <h4>Your last name</h4>
  <input type="text" name="last_name" required>
  </div>
  <div class="field">
  <h4>Your address</h4>
  <input type="text" name="address" required>
  </div>
  <div class="field">
  <h4>Your E-mail</h4>
  <?php echo htmlspecialchars($this->janitorapp['email']) ?>
  </div>
  <div class="field"><h4>Sworn statements</h4>
  <label><input type="checkbox" name="statement_certification" required> I swear and affirm, under penalty of perjury, that the above information is true and correct.</label>
  <label><input type="checkbox" name="statement_terms" required> I have read and accept the terms of the above letter agreement.</label>
  </div>
  <div class="field">
  <h4>Signed on this date of</h4>
  <input id="date-field" type="text" name="date" placeholder="MM/DD/YYYY" pattern="\d{2}/\d{2}/\d{4}" required>
  </div>
  <div class="field">
  <h4>Signature</h4>
  <div class="protip">By typing your full name below, you are providing us with your digital signature, which is as legally binding as your physical signature. Please note that your signature must exactly match the first and last names that you entered at the top of this web form in order for your submission to be successful, and you must include a signature symbol (such as "/s/") before signing your full name.</div>
  <input id="sign-field" type="text" name="signature" pattern="/s/ .+" placeholder="/s/ Your full name" required></div>
  <input type="hidden" name="action" value="sign"><input type="hidden" name="key" value="<?php echo $this->auth_key ?>">
  <button id="submit-btn" type="submit">Submit</button>
</form>
</div>
</body>
</html>
