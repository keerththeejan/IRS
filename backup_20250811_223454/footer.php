  
	<!-- DELETE MODEL -->
	<!-- Modal -->
	<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	    <div class="modal-dialog" >
	        <div class="modal-content">
	            <div class="modal-body text-center">
	               <h1 class="display-4 text-danger"> <i class="fas fa-trash"></i> </h1>
	                <h1 class="font-weight-lighter">Are you sure?</h1>
	                <h4 class="font-weight-lighter"> Do you really want to delete these records? This process cannot be
                      undone. </h4>       
                <p class="debug-url"></p>
	            </div>
	            <div class="modal-footer">
                  <button type="button btn-primary" class="btn btn-secondary" data-dismiss="modal">Close</button>
                  <a class="btn btn-danger btn-ok">Delete</a>
	            </div>
	        </div>
	    </div>
	</div>
	<!-- END DELETE MODEL -->

</div>
</main>
</div>

  <!-- Optional JavaScript -->
  <!-- jQuery first, then Popper.js, then Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Initialize tooltips and popovers
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize tabs
    var tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabEls.forEach(function(tabEl) {
      tabEl.addEventListener('click', function (event) {
        event.preventDefault();
        var tab = new bootstrap.Tab(tabEl);
        tab.show();
      });
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/bootstrap-select.min.js"></script>
  <!-- Custom JavaScript for Permissions -->
  <script src="js/profile-permissions.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>
  <!-- Menu Toggle Script -->
  <script>
// $("#menu-toggle").click(function(e) {
//     e.preventDefault();
//     $("#wrapper").toggleClass("toggled");
// });

jQuery(function ($) {

$(".sidebar-dropdown > a").click(function() {
$(".sidebar-submenu").slideUp(200);
if (
$(this)
  .parent()
  .hasClass("active")
) {
$(".sidebar-dropdown").removeClass("active");
$(this)
  .parent()
  .removeClass("active");
} else {
$(".sidebar-dropdown").removeClass("active");
$(this)
  .next(".sidebar-submenu")
  .slideDown(200);
$(this)
  .parent()
  .addClass("active");
}
});

$("#close-sidebar").click(function() {
$(".page-wrapper").removeClass("toggled");
});
$("#show-sidebar").click(function() {
$(".page-wrapper").addClass("toggled");
});




});

//delete model
$('#confirm-delete').on('show.bs.modal', function(e) {
$(this).find('.btn-ok').attr('href', $(e.relatedTarget).data('href'));
$('.debug-url').html('Delete URL: <strong>' + $(this).find('.btn-ok').attr('href') + '</strong>');
});

var timeDisplay = document.getElementById("timestamp");

function refreshTime() {
    var dateString = new Date().toLocaleString("en-US", {
        timeZone: "Asia/Colombo"
    });
    var formattedString = dateString.replace(", ", " - ");
    timeDisplay.innerHTML = formattedString;
}

// setInterval(refreshTime, 60000);

// $(document).ready(function() {
//     setInterval(timestamp, 1000);
// });

// function timestamp() {
//   var xmlhttp = new XMLHttpRequest();
//         xmlhttp.onreadystatechange = function() {
//             if (this.readyState == 4 && this.status == 200) {
//                 document.getElementById("timestamp").innerHTML = this.responseText;
//             }
//         };
//         xmlhttp.open("GET", "controller/timestamp.php", true);
//         xmlhttp.send();
// }

// //notification sample number
// var x = document.getElementById("notificationx")
// x.innerHTML = Math.floor((Math.random() * 1000) + 1);

// //message sample number
// var x = document.getElementById("messengerx")
// x.innerHTML = Math.floor((Math.random() * 2000) + 1);
  </script>



  </body>

  </html>