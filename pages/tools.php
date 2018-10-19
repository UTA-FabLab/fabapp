<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$auto_off = true;
//Retrive all Materials available for the PolyPrinter
$device_mats = Materials::getDeviceMats(2);
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
                    <i class="far fa-file-alt fa-fw"></i> Color Swap Instructions
                    <div class="pull-right">
                        <button  class="btn btn-xs" data-toggle="collapse" data-target="#swapPanel"><i class="fas fa-bars"></i></button>
                    </div>
                </div>
                <!-- /.panel-heading -->
                <div class="panel-body collapse in" id="swapPanel">
                    <table class="table table-responsive">
                        <tr>
                            <td class="col-md-7">
                                <img src="../images/colorswap.JPG" alt="" class="img-responsive"/>
                            </td>
                            <td class="col-md-5">
                                <p>After the stl has been sliced and the gcode has been saved, view the “Models and Paths” and 
                                    determine which layer you want to pause the printer on. The layer is displayed in KISSlicer as </p>
                                <p>z value = z height in mm</p>
                                <p>Check the values of z as you scroll through the different layers. If you want to change the colors, 
                                    for example, between layers z=10.000 and z=10.250, you will want to pause at the end of layer z=10.000.</p>
                                <p>Enter that value in the field as "10.000"</p>
                                <input disabled value="10.000"/>
                                <p>To do multiple color swaps add a new row for each pause that you want to have. The values must be in 
                                    sequential order. It requires both a valid Z height and a selected color.</p>
                                <p>Upload the gcode file to the right.</p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p>gcode Example</p>
                                <i>G28 X<br>G1 Z20.000<br>M117<br>M0<br>G1 Z10.2497<br>G28 X Y</i>
                            </td>
                            <td>
                                <p>Explanation</p>
                                <p>Zero X-axis<br>Move Z height to 20.000mm<br>Print coordinates to console<br>Pause Printer
                                    <br>Move Z height to correct height<br>Zero X & Y axis</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <!-- /.col-md-8 -->
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-palette fa-fw"></i> Color Swap 
                </div>
                <!-- /.panel-heading -->
                <div class="panel-body">
                    <table class="table-striped table-bordered table-responsive" id="cs_table">
                        <thead>
                            <th style="text-align:center">Location</th>
                            <th style="text-align:center">Color</th>
                        </thead>
                        <tr>
                            <td>
                                <div class ="input-group">
                                    <span class="input-group-addon">Z = </span>
                                    <input type="number" min="0" step=".25" class="form-control loc" placeholder="ex. 10.000"/>
                                </div>
                            </td>
                            <td id="td_select">
                                <select class="form-control dm_select">
                                    <option hidden disabled selected value="">Select Color</option>
                                    <?php foreach($device_mats as $dm){
                                        echo ("<option value='".$dm->getM_id()."'>".$dm->getM_name()."</option>");
                                    }?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <button class="btn btn-success pull-right" onclick="addZ()">Add Additional Color Swaps</button>
                </div>
                <div class="panel-footer">
                    <div class="clearfix">
                        <div class="pull-left">
                            <input type="file" id="files" name="files" accept=".gcode"/>
                            <output id="feedback"></output>
                        </div>
                        <button class="btn pull-right" onclick="swapColors()" disabled id="swapBtn">Submit</button>
                    </div>
                </div>
            </div>
        </div>
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
        
    } else {
        alert('Color Swap is not supported in this browser.');
    }
    
    function addZ(){
        var table = document.getElementById("cs_table");
        var row = table.insertRow(-1);
        var cell1 = row.insertCell(0);
        var cell2 = row.insertCell(1);
        cell1.innerHTML = "<div class ='input-group'><span class='input-group-addon'>Z = </span>"+
            "<input class='form-control loc'/></div>";
        //Duplicate Color DropDown
        cell2.innerHTML = document.getElementById("td_select").innerHTML;
    }
    
    function destroyClickedElement(event) {
        document.body.removeChild(event.target);
    }
    
    function handleFileSelect(evt) {
        var file = evt.target.files[0]; // File
        var regex = new RegExp('[\.]{1}(gcode)$');
        sBtn = document.getElementById('swapBtn');
        
        if (file == null){
            sBtn.classList.remove("btn-primary");
            sBtn.disabled = true;
            return;
        }
        
        var reader = new FileReader();

        reader.onload = function(e) {
            if (file && regex.test(file.name)) {
                sBtn.classList.add("btn-primary");
                sBtn.disabled = false;
                console.log("size "+ (file.size/1024000).toFixed(2) + "mb");
            } else {
                document.getElementById('feedback').innerHTML = "<div class='alert alert-danger' role='alert'> Invalid File Type</div>";
                sBtn.classList.remove("btn-primary");
                sBtn.disabled = true;
                console.log("File Undefined");
            }
        };
        reader.readAsText(file);
    }
    document.getElementById('files').addEventListener('change', handleFileSelect, false);//Sort Just z values
    
    function sortFloat(a,b) {
        return a - b;
    }
    
    function swapColors(){
        //Get File & prep Reader
        var file = document.getElementById('files').files[0];
        var reader = new FileReader();
        //Prepare file name
        var name = file.name.slice(0, file.name.indexOf(".gcode"));
        name = name.replace(" ", "_");
        
        //Array of Z & m_id Locations
        var loc_array = [];
        var mats_array = [];
        var locs = document.getElementsByClassName("loc");
        var dm_selects = document.getElementsByClassName("dm_select")
        for(var i=0; i < locs.length; i++){
            loc = parseFloat(locs[i].value).toFixed(3);
            dm = dm_selects[i].value;
            if (loc != "" && dm != ""){
                //Convert to float w/ 3 Decimals
                loc_array.push(loc);
                mats_array.push(dm);
                name = name+"["+loc+"-"+dm+"]";
            }
        }
        

        reader.onload = function(e) {
            var gcode = e.target.result;
            var counter = 0;
            var pos = 0;
            var p = 0;
            
            for(var i = 0; i < loc_array.length; i++){
                //Find Line
                var p = gcode.indexOf("END_LAYER_OBJECT z="+loc_array[i], pos);
                if (p != -1){
                    //Set destination Z Height
                    zloc = (parseFloat(loc_array[i])+10).toFixed(3)
                    //Find Previous Z height
                    var z = gcode.indexOf("G1", p-100);
                    z = gcode.indexOf("Z", z);
                    z = gcode.slice(z, gcode.indexOf(" ", z));
                    
                    //Go to the following line and insert String
                    p = gcode.indexOf("\n", p);
                    gcode = gcode.slice(0, p)+ "G28 X\nG1 Z"+zloc+"\nM117\nM0\nG1 "+z+"\nG28 X Y" + gcode.slice(p);
                    pos = p;
                    counter++;
                    console.log("Pos:"+pos+", z="+loc_array[i]+", m_id="+mats_array[i]);
                } else {
                    break;
                }
            }
            if (counter == loc_array.length){
                
                name = name + "_colorSwap.gcode";
                console.log(name);
                //Output File to Computer
                var downloadLink = document.createElement("a");
                downloadLink.download = name;
                var blob = new Blob([gcode], {type:"text/plain;charset-utf-8"});
                if (window.webkitURL != null) {
                    // Chrome allows the link to be clicked
                    // without actually adding it to the DOM.
                    downloadLink.href = window.webkitURL.createObjectURL(blob);
                } else {
                    // Firefox requires the link to be added to the DOM
                    // before it can be clicked.
                    downloadLink.href = window.URL.createObjectURL(blob);
                    downloadLink.onclick = destroyClickedElement;
                    downloadLink.style.display = "none";
                    document.body.appendChild(downloadLink);
                }
                
                if (confirm(counter+" valid color swaps, is that correct?")){
                    downloadLink.click();
                }

            } else {
                alert("Unable to find all z values.");
            }
            
        };
        reader.readAsText(file);
    }
    
    String.prototype.insert = function (index, string) {
        if (index > 0)
          return this.substring(0, index) + string + this.substring(index, this.length);
        else
          return string + this;
    };
</script>