function showLoading() {
  $("#loadingIndicator").show();
}

// Function to hide loading indicator
function hideLoading() {
  $("#loadingIndicator").hide();
}


$(function () {
  $('#myForm').on("submit", function(event){
        event.preventDefault();
	
	showLoading();

        $.ajax({
          method: "POST",
          url: 'get_data.php',
          data: $('#myForm').serialize(),
          cache: false,
          success: function(data){
		  hideLoading();
                  location.reload();
          },
          error: function(jqXHR, textStatus, errorThrown){
		  hideLoading();
                  console.log("Error: ", textStatus, errorThrown);
                  alert("Form submission failed!"); 
          }
        });
   });
});
