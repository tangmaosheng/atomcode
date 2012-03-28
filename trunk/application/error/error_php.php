<?php
if (!isset($GLOBALS['___error_counter_php'])) {
	$GLOBALS['___error_counter_php'] = 0;
}

if (!$GLOBALS['___loaded_header_php']) {
$GLOBALS['___loaded_header_php'] = true;
?>
<style type="text/css">
<!--
html * {
	padding:0;
	margin:0;
}
body * {
	padding:10px 20px;
}
body * * {
	padding:0;
}
body {
	font:small sans-serif;
}
body>div {
	border-bottom:1px solid #ddd;
}
h1 {
	font-weight:bold;
	padding-bottom:20px;
}
h2 {
	margin-bottom:.8em;
}
h2 span {
	font-size:12px;
	color:#666;
	font-weight:normal;
}
h3 {
	margin:1em 0 .5em 0;
}
h4 {
	margin:0 0 .5em 0;
	font-weight: normal;
}
.requestinfo1 {
	background:#f6f6f6;
	padding-left:120px;
}
.requestinfo0 {
	background:#eee;
	padding-left:120px;
}
code, pre {
	font-size: 100%;
}
table {
	border:1px solid #ccc;
	border-collapse: collapse;
	width:100%;
	background:white;
}
tbody td, tbody th {
	vertical-align:top;
	padding:2px 3px;
}
thead th {
	padding:1px 6px 1px 3px;
	background:#fefefe;
	text-align:left;
	font-weight:normal;
	font-size:11px;
	border:1px solid #ddd;
}
tbody th {
	width:12em;
	text-align:right;
	color:#666;
	padding-right:.5em;
}
table.req td {
	font-family:monospace;
}
#summary {
	background: #ffc;
}
#explanation {
	background:#eee;
}
#template, #template-not-exist {
	background:#f6f6f6;
}
#template-not-exist ul {
	margin: 0 0 0 20px;
}
#unicode-hint {
	background:#eee;
}
#traceback {
	background:#eee;
}
#requestinfo {
	background:#f6f6f6;
	padding-left:120px;
}
#summary table {
	border:none;
	background:transparent;
}
#requestinfo h2, #requestinfo h3 {
	position:relative;
	margin-left:-100px;
}
#requestinfo h3 {
	margin-bottom:-1em;
}
.error {
	background: #ffc;
}
.specific {
	color:#cc3300;
	font-weight:bold;
}
h2 span.commands {
	font-size:.7em;
}
span.commands a:link {
	color:#5E5694;
}
pre.exception_value {
	font-family: sans-serif;
	color: #666;
	font-size: 1.5em;
	margin: 10px 0 10px 0;
}
-->
</style>
<div id="summary">
  <h1>Error Reporting</h1>
  <table class="meta">
    <tbody>
      <tr>
        <th>Request Method:</th>
        <td><?php echo $_SERVER['REQUEST_METHOD']; ?></td>
      </tr>
      <tr>
        <th>Request URI:</th>
        <td><?php echo $_SERVER['REQUEST_URI']; ?></td>
      </tr>
      <tr>
        <th>Path Info:</th>
        <td><?php echo $_SERVER['PATH_INFO']; ?></td>
      </tr>
      <tr>
        <th>AtomCode Version:</th>
        <td><?php echo VERSION; ?></td>
      </tr>
      <tr>
        <th>Server time:</th>
        <td><?php echo date('Y-m-d H:i:s'); ?></td>
      </tr>
    </tbody>
  </table>
</div>
<?php } ?>
<div class="requestinfo<?php echo ++$GLOBALS['___error_counter_php'] % 2; ?>">
  <h2><?php echo $severity; ?> Messages</h2>
  <table class="req">
    <tbody>
      <tr>
        <td><b>Severity</b></td>
        <td class="code"><?php echo $severity; ?></td>
      </tr>
      <tr>
        <td><b>Message</b></td>
        <td class="code"><?php echo $message; ?></td>
      </tr>
      <tr>
        <td><strong>Filename</strong></td>
        <td class="code"><?php echo $filepath; ?></td>
      </tr>
      <tr>
        <td><strong>Line Number</strong></td>
        <td class="code"><?php echo $line; ?></td>
      </tr>
    </tbody>
  </table>
</div>
