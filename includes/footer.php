  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var sidebar = document.getElementById('sidebarMenu');
      var brand = document.getElementById('brandTitle');
      if (sidebar && brand) {
        sidebar.addEventListener('show.bs.offcanvas', function() {
          brand.classList.add('brand-shift');
        });
        sidebar.addEventListener('hide.bs.offcanvas', function() {
          brand.classList.remove('brand-shift');
        });
      }
    });
  </script>
  </body>


  </html>