<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

?>
<title><?php echo $sv['site_name'];?> Tools</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-header">Tools</h1>
            And other helpful stuff.
        </div>
        <!-- /.col-md-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-palette fa-fw"></i> Color Swap
                </div>
                <div class="panel-body">
                    <table class="table table-responsive">
                        <tr>
                            <td class="col-md-8">
                                <img src="../images/colorswap.JPG" alt="" class="img-responsive"/>
                            </td>
                            <td class="col-md-4">
                                <p>After the stl has been sliced and the gcode has been saved, go to the view “Models and Paths” and 
                                    determine which layer you want to pause the printer on. The way the layer is displayed in KISSlicer is </p>
                                <p>         “z = height in mm”</p>
                                <p>Check the values of z as you toggle through the different layers. If you want to change the colors, 
                                    for example, between layers z=10.000 and z=10.250, you will want to pause at the end of layer z=10.000.</p>
                                <p>Enter that value in the field below as "10.000." </p>
                                <input disabled value="10.000"/>
                                <p>To do multiple color swaps use a comma to separate each number. Please keep them in ascending order.</p>
                                <input disabled value="10.000, 15.000, 17.000"/>
                                <p>Upload the gcode file below.</p>
                            </td>
                        <tr>
                            <td>
                                Enter the Z height(s) needed for color swaps. 
                                <input placeholder="ex. 10.000, 15.000" />
                            </td>
                            <td>
                                <input type="file" id="files" name="files"  accept=".gcode"/>
                                <output id="list"></output>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"><button class="btn btn-primary">Submit</button></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <!-- /.col-md-8 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script>
    if (window.File && window.FileReader && window.FileList && window.Blob) {
        function handleFileSelect(evt) {
            var files = evt.target.files; // FileList object

            // Loop through the FileList and render image files as thumbnails.
            for (var i = 0, f; f = files[i]; i++) {

              // Only process image files.
              if (!f.type.match('.gcode')) {
                continue;
              }

              var reader = new FileReader();

              // Closure to capture the file information.
              reader.onload = (function(theFile) {
                return function(e) {
                  // Render thumbnail.
                  var span = document.createElement('span');
                  span.innerHTML = ['<img class="thumb" src="', e.target.result,
                                    '" title="', escape(theFile.name), '"/>'].join('');
                  document.getElementById('list').insertBefore(span, null);
                };
              })(f);

              reader.readAsText(f);
              alert('File Just read');
            }
        }
        document.getElementById('files').addEventListener('change', handleFileSelect, false);
    } else {
        alert('Color Swap is not supported in this browser.');
    }
</script>