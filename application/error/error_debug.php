<?php 
if (!isset($GLOBALS['___loaded_header_debug'])) {
	$GLOBALS['___loaded_header_debug'] = false;
}
static $___error_counter_debug = 0;
if (!$GLOBALS['___loaded_header_debug']) {
	function get_var($var) {
		if (is_object($var)) {
			return get_class($var);
		} elseif (is_array($var)) {
			return 'Array';
		} else {
			return $var;
		}
	}
$GLOBALS['___loaded_header_debug'] = true;
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
  <h1>Debug Reporting</h1>
  <table class="meta">
    <tbody>
    <?php if ($is_exception) { ?>
      <tr>
        <th>Exception Code</th>
        <td><?php echo $obj->getCode(); ?></td>
      </tr>
      <tr>
        <th>Exception Message</th>
        <td><?php echo $obj->getMessage(); ?></td>
      </tr>
    <?php } ?>
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
<div class="requestinfo<?php echo $___error_counter_debug ++ % 2; ?>">
  <h2>Exception Messages</h2>
  <?php foreach ($trace as $key => $t) { ?>
  <h3>#<?php echo $key; ?></h3>
  <table class="req">
    <tbody>
      <tr>
        <td width="200"><b>Position</b></td>
        <td class="code"><?php if ($t['file']) {echo "$t[file] (<b>$t[line]</b>)";} else { echo '[internal function]';} ?></td>
      </tr>
      <?php if ($t['function']) { ?>
      <tr>
        <td><b>Caller Info</b></td>
        <td class="code"><?php echo $t['class'] ? $t['class'] . $t['type'] . $t['function'] : $t['function'];
		if (count($t['args']) == 0) { echo '()'; }
		else { echo '(' . get_var($t['args'][0]);
				array_shift($t['args']);
				foreach ($t['args'] as $arg) { echo ', ' . get_var($arg);}
		echo ')'; }
		?></td>
      </tr>
      <?php } ?>
</tbody>
  </table>
  <?php } ?>
</div>

<div class="requestinfo<?php echo $___error_counter_debug ++ % 2; ?>">
  <h2>Config Items</h2>
  <table class="req">
    <tbody>
  <?php global $config; foreach ($config as $key => $item) { ?>
      <tr>
        <td width="200"><b><?php echo $key;?></b></td>
        <td class="code"><pre><?php echo var_export($item, true); ?></pre></td>
      </tr>
  <?php } ?>
</tbody>
  </table>
</div>

<div class="requestinfo<?php echo $___error_counter_debug ++ % 2; ?>">
  <h2>Get Items</h2>
  <table class="req">
    <tbody>
  <?php  foreach ($_GET as $key => $item) { ?>
      <tr>
        <td width="200"><b><?php echo $key;?></b></td>
        <td class="code"><pre><?php echo var_export($item, true); ?></pre></td>
      </tr>
  <?php } ?>
</tbody>
  </table>
</div>


<div class="requestinfo<?php echo $___error_counter_debug ++ % 2; ?>">
  <h2>Post Items</h2>
  <table class="req">
    <tbody>
  <?php  foreach ($_POST as $key => $item) { ?>
      <tr>
        <td width="200"><b><?php echo $key;?></b></td>
        <td class="code"><pre><?php echo var_export($item, true); ?></pre></td>
      </tr>
  <?php } ?>
</tbody>
  </table>
</div>

<div class="requestinfo<?php echo $___error_counter_debug ++ % 2; ?>">
  <h2>Cookie Items</h2>
  <table class="req">
    <tbody>
  <?php  foreach ($_COOKIE as $key => $item) { ?>
      <tr>
        <td width="200"><b><?php echo $key;?></b></td>
        <td class="code"><pre><?php echo var_export($item, true); ?></pre></td>
      </tr>
  <?php } ?>
</tbody>
  </table>
</div>

<div class="requestinfo<?php echo $___error_counter_debug ++ % 2; ?>">
  <h2>Server Items</h2>
  <table class="req">
    <tbody>
  <?php  foreach ($_SERVER as $key => $item) { ?>
      <tr>
        <td width="200"><b><?php echo $key;?></b></td>
        <td class="code"><pre><?php echo var_export($item, true); ?></pre></td>
      </tr>
  <?php } ?>
</tbody>
  </table>
</div>
