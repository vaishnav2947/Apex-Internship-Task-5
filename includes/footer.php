<?php
/**
 * includes/footer.php
 * Shared closing tags + scripts on every page.
 */
?>
</div><!-- /.container (opened in header.php) -->
</main>

<!-- ── FOOTER ─────────────────────────────────────────────────────────── -->
<footer class="footer py-4 mt-auto">
  <div class="container">
    <div class="row align-items-center gy-2">
      <div class="col-md-6 text-center text-md-start">
        <span class="fw-semibold text-white">ApexPlanet Blog</span>
        <span class="text-white-50 ms-2">
          &copy; <?= date('Y') ?> — 45-Day PHP &amp; MySQL Internship
        </span>
      </div>
      <div class="col-md-6 text-center text-md-end">
        <span class="badge bg-primary me-1">PHP 8.2</span>
        <span class="badge bg-warning text-dark me-1">MySQL</span>
        <span class="badge bg-purple me-1">Bootstrap 5</span>
        <span class="badge bg-success">PDO</span>
      </div>
    </div>
  </div>
</footer>

<!-- Bootstrap 5 JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="/apexplanet-internship/assets/js/main.js"></script>
</body>
</html>
