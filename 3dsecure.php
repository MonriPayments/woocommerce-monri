<!DOCTYPE html>
<html>
  <head>
    <title>Monri 3D Secure Verification</title>
     <script language='Javascript'>
        function OnLoadEvent() { document.form.submit(); }
    </script>
  </head>
  <body OnLoad='OnLoadEvent();' style="display: none">
    Invoking 3-D secure form, please wait ...
    <form name='form' action='<?php echo $_GET['acsUrl'] ?>' method='post'>
      <input  class="form-control" type='hidden' name='PaReq' value='<?php echo $_GET['pareq'] ?>'>
      <input  class="form-control" type='hidden' name='TermUrl' value='<?php echo $_GET['returnUrl'] ?>'>
      <input  class="form-control" type='hidden' name='MD' value='<?php echo $_GET['token'] ?>'>
      <noscript>
        <p>Please click</p><input id='to-asc-button' type='submit'>
      </noscript>
    </form>
  </body>
</html>