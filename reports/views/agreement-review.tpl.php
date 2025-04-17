<?php if (!defined('IN_APP')) die() ?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="referrer" content="never">
  <title>Agreement Review</title>
  <link rel="stylesheet" type="text/css" href="/css/agreement.css?11">
  <link rel="shortcut icon" href="/image/favicon-team.ico" type="image/x-icon">
  <script type="text/javascript">
    function onDocumentClick(e) {
      var id, msg, otp;
      
      if (e.target === document) {
        return;
      }
      
      if (e.target.hasAttribute('data-sign')) {
        id = e.target.getAttribute('data-sign');
        
        if (!id) {
          console.log('Bad ID');
          return;
        }
        
        otp = prompt('One-time Password');
        
        if (!otp) {
          console.log('Canceled');
          return;
        }
        
        window.open(
          'https://reports.4chan.org/agreement.php?action=counter_sign&id='
          + id + '&otp=' + otp
          + '&key=' + document.body.getAttribute('data-key')
       );
      }
      else if (e.target.hasAttribute('data-preview')) {
        id = e.target.getAttribute('data-preview');
        
        if (!id) {
          console.log('Bad ID');
          return;
        }
        
        otp = prompt('One-time Password');
        
        if (!otp) {
          console.log('Canceled');
          return;
        }
        
        window.open(
          'https://reports.4chan.org/agreement.php?action=counter_sign&preview=1&id='
          + id
          + '&otp=' + otp
          + '&key=' + document.body.getAttribute('data-key')
       );
      }
      else if (e.target.hasAttribute('data-reject')) {
        id = e.target.getAttribute('data-reject');
        
        if (!id) {
          console.log('Bad id');
          return;
        }
        
        msg = prompt('Reason');
        
        if (!msg) {
          console.log('Canceled');
          return;
        }
        
        window.open(
          'https://reports.4chan.org/agreement.php?action=reject&id='
          + id + '&msg=' + encodeURIComponent(msg)
          + '&key=' + document.body.getAttribute('data-key')
        );
      }
    }
    
    document.addEventListener('click', onDocumentClick, false);
  </script>
</head>
<body data-key="<?php echo htmlspecialchars($_GET['key'], ENT_QUOTES) ?>">
<header>
  <h1 id="title">Agreement Review</h1>
</header><?php $inc_warn = array();
if ($this->htpasswd_over_lines) {
  $inc_warn[] = 'The htpasswd file has too many lines.';
}
if ($this->workdir_over_size) {
  $inc_warn[] = 'The working directory contains too many files.';
}
if ($inc_warn):
?>
<div id="inc-warn">
  <?php echo implode('<br>', $inc_warn) ?>
</div>
<?php endif ?>
<table id="doc-table" class="items-table">
<thead>
  <tr>
    <th>User name</th>
    <th>E-mail</th>
    <th>First name</th>
    <th>Last name</th>
    <th>Signature</th>
    <th>Signed on</th>
    <th>Created on</th>
    <th></th>
  </tr>
</thead>
<tbody>
<?php foreach ($this->documents as $doc): ?>
  <tr>
    <td><?php echo $doc['user_name'] ?></td>
    <td><?php echo htmlspecialchars($doc['email'], ENT_QUOTES) ?></td>
    <td><?php echo $doc['first_name'] ?></td>
    <td><?php echo $doc['last_name'] ?></td>
    <td><?php echo $doc['signature'] ?></td>
    <td><?php echo $doc['sign_date'] ?></td>
    <td><?php echo date('m/d/y H:i', $doc['created_on']) ?></td>
    <td><span class="button btn-other" data-preview="<?php echo $doc['id'] ?>">Preview</span><span class="button btn-accept" data-sign="<?php echo $doc['id'] ?>">Sign</span><span data-reject="<?php echo $doc['id'] ?>" class="button btn-deny">Reject</span></td>
  </tr>
<?php endforeach ?>
</table>
</body>
</html>
