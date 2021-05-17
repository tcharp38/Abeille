<?php
    if (!isConnect('admin')) {
        throw new Exception('{{401 - Accès non autorisé}}');
    }

    require_once __DIR__.'/../../core/class/AbeilleTools.class.php';
    $jsonCmdsList = AbeilleTools::getCommandsList();
?>

<div style="margin: 10px 10px">
    Selectionner la commande à ajouter à partir du fichier JSON.
    <br>
    <br>
    <select class="form-control input-sm" title="{{Commandes internes Abeille}}">
        <?php
        foreach ($jsonCmdsList as $file) {
            echo "<option value=\"".$file."\">".$file."</option>";
        }
        ?>
    </select>
    <br>
    <br>
    <br>
    <a id="idLoadCmdJson" class="btn btn-danger pull-middle">{{Charger}}</a>
</div>

<script>
    $('#idLoadCmdJson').on('click', function () {
        console.log("idLoadCmdJson() click");

        // $("#bt_addAbeilleAction").on('click', function(event) {
        //     var _cmd = {type: 'action'};
        //     addCmdToTable(_cmd);
        //     $('.cmd:last .cmdAttr[data-l1key=type]').trigger('change')
        //     $('#div_alert').showAlert({message: 'Nouvelle commande action ajoutée en fin de tableau. A compléter et sauvegarder.', level: 'success'});
        // });

        // $("#bt_addAbeilleInfo").on('click', function(event) {
        //     var _cmd = {type: 'info'};
        //     addCmdToTable(_cmd);
        //     $('.cmd:last .cmdAttr[data-l1key=type]').trigger('change')
        //     $('#div_alert').showAlert({message: 'Nouvelle commande info ajoutée en fin de tableau. A compléter et sauvegarder.', level: 'success'});
        // });
    });
</script>
