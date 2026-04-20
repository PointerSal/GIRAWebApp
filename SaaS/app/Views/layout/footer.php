  </main><!-- /main-content -->
  </div><!-- /app-wrapper -->
  <?php if (isset($extra_js)) echo $extra_js; ?>
  <?php if (Auth::isLogged()): ?>
    <script>
      window.GIRA_VAPID_PUBLIC = '<?= VAPID_PUBLIC ?>';
      window.APP_URL = '<?= APP_URL ?>';
    </script>
    <script src="<?= APP_URL ?>/assets/js/push.js"></script>
  <?php endif; ?>
  </body>

  </html>