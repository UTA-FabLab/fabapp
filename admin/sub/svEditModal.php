<?php

/* 
 * License - FabApp V 0.91
 * 2016-2018 CC BY-NC-AS UTA FabLab
 */

include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/connections/db_connect8.php');
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/class/all_classes.php');

if (!empty(filter_input(INPUT_GET, "sv_id"))) { 
    $sv_o = new Site_Variables(filter_input(INPUT_GET, "sv_id")); ?>
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <form name="svForm" method="post" action="">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="svTitle">Edit <?php echo $sv_o->getName();?></h4>
            </div>
            <div class="modal-body" id="svBody">
                <table class="table table-bordered table-striped">
                    <tr>
                        <td>
                            Notes
                        </td>
                        <td>
                            <?php echo $sv_o->getNotes();?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Value
                        </td>
                        <td>
                            <input type="text" value="<?php echo $sv_o->getvalue();?>" name="value_<?php echo $sv_o->getId();?>">
                        </td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                  <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-primary" name="Save">Save</button>
            </div> 
            </form>
        </div>
    </div>
<?php } ?>