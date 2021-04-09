<!-- This is equipement discovery page.
     Allows to interrogate eq to get useful infos
     - list of EP
     - list of clusters
     - list of attributes
     URL of this page should contain 'id' of EQ
     -->

<?php
    require_once __DIR__.'/../../resources/AbeilleDeamon/includes/config.php';

    /* Developers debug features & PHP errors */
    // $dbgFile = __DIR__."/../../tmp/debug.json";
    if (file_exists(dbgFile)) {
        $dbgDeveloperMode = TRUE;
        echo '<script>var js_dbgDeveloperMode = '.$dbgDeveloperMode.';</script>'; // PHP to JS
        /* Dev mode: enabling PHP errors logging */
        error_reporting(E_ALL);
        ini_set('error_log', __DIR__.'/../../../../log/AbeillePHP.log');
        ini_set('log_errors', 'On');
    }

    if (!isset($_GET['id']))
        exit("INTERNAL ERROR: Missing 'id'");
    if (!is_numeric($_GET['id']))
        exit("INTERNAL ERROR: 'id' is not numeric");

    $eqId = $_GET['id'];
    $eqLogic = eqLogic::byId($eqId);
    $eqLogicId = $eqLogic->getLogicalid();
    list($eqNet, $eqAddr) = explode( "/", $eqLogicId);
    if ($eqAddr == "Ruche")
        $eqAddr = "0000";
    $zgNb = substr($eqNet, 7); // Extracting zigate number from network
    $jsonName = $eqLogic->getConfiguration('modeleJson', '');
    $jsonPath = __DIR__.'/../../core/config/devices/'.$jsonName.'/'.$jsonName.'.json';

    echo '<script>var js_zgNb = '.$zgNb.';</script>'; // PHP to JS
    echo '<script>var js_eqId = '.$eqId.';</script>'; // PHP to JS
    echo '<script>var js_eqAddr = "'.$eqAddr.'";</script>'; // PHP to JS
    echo '<script>var js_jsonName = "'.$jsonName.'";</script>'; // PHP to JS

    // require_once __DIR__.'/../../resources/AbeilleDeamon/includes/config.php';
    define("MAXEP", 10); // Max number of End Points
?>

<!-- <div class="col-xs-12"> -->
    <h3>Assistant de découverte d'équipement (sur SECTEUR uniquement)</h3>
    <br>

    <form>
        <!-- <div class="row"> -->
        <div class="col-lg-6">
            <div class="row">
                <label class="col-lg-2 control-label" for="fname">ID Jeedom:</label>
                <div class="col-lg-2">
                    <?php echo '<input type="text" value="'.$eqId.'" readonly>'; ?>
                </div>
            </div>
            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Adresse:</label>
                <div class="col-lg-2">
                    <?php echo '<input type="text" value="'.$eqAddr.'" readonly>'; ?>
                </div>
            </div>
            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Fichier JSON:</label>
                <div class="col-lg-10">
                    <?php
                        if ($jsonName == '')
                            echo '<input type="text" value="-- Non défini --" readonly>';
                        else if (!file_exists(__DIR__.'/../../core/config/devices/'.$jsonName.'/'.$jsonName.'.json'))
                            echo '<input type="text" value="'.$jsonName.' (n\'existe pas)" readonly>';
                        else
                            echo '<input type="text" value="'.$jsonName.'" readonly>';
                    ?>
                    <a class="btn btn-warning" title="(Re)lire" onclick="readJSON()">(Re)lire</a>
                    <a class="btn btn-warning" title="Mettre à jour" onclick="writeJSON()">Mettre à jour</a>
                    <!-- <a class="btn btn-warning" title="Fonction test" onclick="toto()">TOTO</a> -->
                </div>
            </div>
            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Fabricant:</label>
                <div class="col-lg-10">
                    <input type="text" value="" id="idManuf">
                </div>
            </div>
            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Modèle/ref:</label>
                <div class="col-lg-10">
                    <input type="text" value="" id="idModel">
                </div>
            </div>
            <div class="row">
                <label class="col-lg-2 control-label" for="fname">nameJeedom:</label>
                <div class="col-lg-10">
                    <input type="text" value="" id="idDesc">
                </div>
            </div>

            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Timeout (min):</label>
                <div class="col-lg-10">
                    <input type="text" value="" id="idTimeout">
                </div>
            </div>
            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Catégorie:</label>
                <div class="col-lg-10">
                <?php
                    /* See jeedom.config.php */
                    $categories = "";
                    foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                        echo '<label class="checkbox-inline">';
                        echo '<input type="checkbox" id="id'.$key.'" />'.$value['name'];
                        echo '</label>';
                        if ($categories != "")
                            $categories .= ", ";
                        $categories .= "'".$key."'";
                    }
                    echo "<script>var js_categories=[".$categories."];</script>";
                ?>
                </div>
            </div>
            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Icone:</label>
                <div class="col-lg-10">
                    <input type="text" value="" id="idIcon">
                </div>
            </div>
            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Batterie:</label>
                <div class="col-lg-10">
                    <input type="text" value="" id="idBattery">
                </div>
            </div>

            <div id="idCommands">
            </div>
        </div>

        <!-- Colonne Zigbee -->
        <div class="col-lg-6">
            <div class="row">
                <a class="btn btn-warning" title="Réinterroge l'équipement" onclick="interrogateEq()">Tout raffraichir: <i class="fas fa-sync"></i></a>
                <label class="col-sm-2 control-label" for="fname">Status:</label>
                <div class="col-sm-4">
                    <input type="text" id="idStatus" value="" readonly>
                </div>
            </div>

            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Manufacturer:</label>
                <div class="col-lg-10">
                    <a class="btn btn-warning" title="Raffraichi le nom du fabricant" onclick="refreshXName('Manuf')"><i class="fas fa-sync"></i></a>
                    <input type="text" id="idZigbeeManuf" value="" readonly>
                </div>
            </div>

            <div class="row">
                <label class="col-lg-2 control-label" for="fname">Model:</label>
                <div class="col-lg-10">
                    <a class="btn btn-warning" title="Raffraichi le nom du model" onclick="refreshXName('Model')"><i class="fas fa-sync"></i></a>
                    <input type="text" id="idZigbeeModel" value="" readonly>
                </div>
            </div>

            <div class="row">
                <label class="col-lg-2 control-label" for="fname">End points:</label>
                <div class="col-lg-10">
                    <a class="btn btn-warning" title="Raffraichi la liste des End Points" onclick="refreshEPList()"><i class="fas fa-sync"></i></a>
                    <input type="text" id="idEPList" value="" readonly>
                </div>
            </div>

            <style>
                table, td {
                    border: 1px solid black;
                }
            </style>

            <?php
            // idClustLabx => EPx clusters label
            // idInClustx => table of input clusters (col1=clustId, col2+=attribut)
            // idOutClustx => table of output clusters (col1=clustId, col2+=attribut)
            for ($epIdx = 0; $epIdx < MAXEP; $epIdx++) {
                echo '<div id="idEP'.$epIdx.'" class="row" style="display:none">';
                    echo '<label id="idClustLab'.$epIdx.'" class="col-lg-2 control-label"></label>';
                    echo '<div class="col-lg-10">';
                    echo '<a class="btn btn-warning" title="Raffraichi la liste des clusters" onclick="refreshClustersList('.$epIdx.')"><i class="fas fa-sync"></i></a>';
                    echo '<br><br>';

                    echo '</div>';

                    echo '<label for="fname">Clusters IN:</label>';
                    echo '<br>';
                    echo '<table id="idInClust'.$epIdx.'">';
                    echo '</table>';
                    echo '<br>';

                    echo '<label for="fname">Clusters OUT:</label>';
                    echo '<table id="idOutClust'.$epIdx.'">';
                    echo '</table>';
                    echo '<br>';
                echo '</div>';
            }
            ?>
        </div>
        <!-- </div> -->
    </form>

<!-- </div> -->

<script>
    var eq = new Object(); // Equipement details
    eq.zgNb = js_zgNb; // Zigate number, number
    eq.id = js_eqId; // Jeedom ID, number
    eq.addr = js_eqAddr; // Short addr, hex string
    eq.epCount = 0; // Number of EP, number
    eq.epList = new Array(); // Array of objects
        // ep = eq.epList[epIdx] = new Object(); // End Point object
        // ep.id = 0; // EP id/number
        // ep.inClustCount = 0; // IN clusters count
        // ep.inClustList = new Array();
        // ep.outClustCount = 0; // OUT clusters count
        // ep.outClustList = new Array();
        //     clust = new Object();
        //     clust.id = "0000"; // Cluster id, hex string
        //     clust.attrList = new Array(); // Attributs for this cluster
        //         a = new Object(); // Attribut object
        //         a.type = "00"; // Attribut type, hex string
        //         a.id = "0000"; // Attribut id, hex string

    /* Attempt to detect main supported attributs */
    function refreshAttributsList(epIdx, outClust, clustIdx) {
        console.log("refreshAttributsList(epIdx="+epIdx+", outClust="+outClust+", clustIdx="+clustIdx+")");

        ep = eq.epList[epIdx];
        epNb = ep.id;
        if (outClust)
            clust = ep.outClustList[clustIdx];
        else
            clust = ep.inClustList[clustIdx];
        clustId = clust.id;

        // idInClustx => table of input clusters (col1=clustId, col2+=attribut)
        // idOutClustx => table of output clusters (col1=clustId, col2+=attribut)
        if (outClust) {
            var clustTable = document.getElementById("idOutClust"+epIdx);
            var line = clustTable.rows[clustIdx];
        } else {
            var clustTable = document.getElementById("idInClust"+epIdx);
            var line = clustTable.rows[clustIdx];
        }
        /* Cleanup tables: remove all columns except first one (cluster ID) */
        var colCount = line.cells.length;
        for (var i = colCount - 1; i >= 1; i--) {
            line.deleteCell(i);
        }

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleEqAssist.ajax.php',
            data: {
                action: 'detectAttributs',
                zgNb: js_zgNb,
                eqAddr: js_eqAddr,
                eqEP: epNb, // EP number
                clustId: clustId,
            },
            dataType: 'json',
            global: false,
            async: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'detectAttributs' !<br>Votre installation semble corrompue.<br>"+error);
            },
            success: function (json_res) {
                res = JSON.parse(json_res.result);
                if (res.status != 0)
                    console.log("error="+res.error);
                else {
                    console.log("res.resp follows:");
                    console.log(res.resp);
                    var resp = res.resp;

                    attributes = resp.Attributes;
                    let attrCount = attributes.length;
                    console.log("nb of attr="+attrCount)
                    for (attrIdx = 0; attrIdx < attrCount; attrIdx++) {
                        rattr = attributes[attrIdx];

                        a = new Object();
                        a.type = rattr.Type;
                        a.id = rattr.Id;
                        clust.attrList.push(a);

                        var newCol = line.insertCell(-1);
                        newCol.innerHTML = rattr.Id;
                    }
                }
            }
        });
    }

    function getAttributsList(epIdx, outClust, clustIdx) {
        console.log("getAttributsList(epIdx="+epIdx+", outClust="+outClust+", clustIdx="+clustIdx+")");

        ep = eq.epList[epIdx];
        epNb = ep.id;
        if (outClust)
            clust = ep.outClustList[clustIdx];
        else
            clust = ep.inClustList[clustIdx];
        clustId = clust.id;
        document.getElementById("idStatus").value = "EP"+epNb+"/Clust"+clustId+": recherche des 'Attributs'";

        // idInClustx => table of input clusters (col1=clustId, col2+=attribut)
        // idOutClustx => table of output clusters (col1=clustId, col2+=attribut)
        if (outClust) {
            var clustTable = document.getElementById("idOutClust"+epIdx);
            var line = clustTable.rows[clustIdx];
        } else {
            var clustTable = document.getElementById("idInClust"+epIdx);
            var line = clustTable.rows[clustIdx];
        }
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleEqAssist.ajax.php',
            data: {
                action: 'getAttrDiscResp',
                zgNb: js_zgNb,
                eqAddr: js_eqAddr,
                eqEP: epNb, // EP number
                clustId: clustId,
            },
            dataType: 'json',
            global: false,
            async: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'getAttrDiscResp' !<br>Votre installation semble corrompue.<br>"+error);
            },
            success: function (json_res) {
                res = JSON.parse(json_res.result);
                if (res.status != 0)
                    console.log("error="+res.error);
                else {
                    console.log("res.resp follows:");
                    console.log(res.resp);
                    var resp = res.resp;

                    a = new Object();
                    a.type = resp.AttrType;
                    a.id = resp.AttrId;
                    clust.attrList.push(a);

                    var newCol = line.insertCell(-1);
                    newCol.innerHTML += a.id+"/"+a.type;

                    document.getElementById("idStatus").value = "";
                }
            }
        });
    }

    /* Interrogate EQ to get clusters list. */
    function refreshClustersList(epIdx) {
        console.log("refreshClustersList(epIdx="+epIdx+")");

        ep = eq.epList[epIdx];
        epNb = ep.id;
        document.getElementById("idStatus").value = "EP"+epNb+": recherche des 'Clusters'";

        // idInClustx => table of input clusters (col1=clustId, col2+=attribut)
        // idOutClustx => table of output clusters (col1=clustId, col2+=attribut)
        var inClustTable = document.getElementById("idInClust"+epIdx);
        var outClustTable = document.getElementById("idOutClust"+epIdx);
        /* Cleanup tables */
        var rowCount = inClustTable.rows.length;
        for (var i = rowCount - 1; i >= 0; i--) {
            inClustTable.deleteRow(i);
        }
        rowCount = outClustTable.rows.length;
        for (i = rowCount - 1; i >= 0; i--) {
            outClustTable.deleteRow(i);
        }

        /* Do the request to EQ */
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleEqAssist.ajax.php',
            data: {
                action: 'getSingleDescResp',
                zgNb: js_zgNb,
                eqAddr: js_eqAddr,
                eqEP: epNb, // EP number
            },
            dataType: 'json',
            global: false,
            async: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'getSingleDescResp' !<br>Votre installation semble corrompue.<br>"+error);
                document.getElementById("idStatus").value = "ERROR clusters";
            },
            success: function (json_res) {
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    console.log("error="+res.error);
                    status = -1;
                    document.getElementById("idStatus").value = "ERROR clusters";
                } else {
                    console.log("res.resp follows:");
                    console.log(res.resp);
                    var resp = res.resp;

console.log("eq follows:");
console.log(eq);
                    ep.inClustCount = resp.InClustCount;
                    ep.inClustList = []; // List of objects
                    ep.outClustCount = resp.OutClustCount;
                    ep.outClustList = []; // List of objects
                    for (clustIdx = 0; clustIdx < resp.InClustCount; clustIdx++) {
                        clust = new Object();
                        clust.id = resp.InClustList[clustIdx];
                        clust.attrList = new Array();
                        ep.inClustList.push(clust);

                        var newLine = inClustTable.insertRow(-1);
                        var newCol = newLine.insertCell(0);
	                    newCol.innerHTML = resp.InClustList[clustIdx]
                        newCol.innerHTML += '<a class="btn btn-warning" title="Raffraichi la liste des attributs" onclick="refreshAttributsList('+epIdx+', 0, '+clustIdx+')"><i class="fas fa-sync"></i></a>';
                    }
                    for (clustIdx = 0; clustIdx < resp.OutClustCount; clustIdx++) {
                        clust = new Object();
                        clust.id = resp.OutClustList[clustIdx];
                        clust.attrList = new Array();
                        ep.outClustList.push(clust);

                        var newLine = outClustTable.insertRow(-1);
                        var newCol = newLine.insertCell(0);
	                    newCol.innerHTML = resp.OutClustList[clustIdx];
                        newCol.innerHTML += '<a class="btn btn-warning" title="Raffraichi la liste des attributs" onclick="refreshAttributsList('+epIdx+', 1, '+clustIdx+')"><i class="fas fa-sync"></i></a>';
                    }
                    status = 0;
                    document.getElementById("idStatus").value = "";
                }
            }
        });
        console.log("refreshClustersList() END, status="+status);
        return status;
    }

    /* Request EP list from EQ.
       Returns: Ajax promise */
    function refreshEPList() {
        console.log("refreshEPList()");

        document.getElementById("idStatus").value = "Recherche des 'End Points'";
        return $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleEqAssist.ajax.php',
            data: {
                action: 'getEPList',
                zgNb: js_zgNb,
                eqAddr: js_eqAddr,
            },
            dataType: 'json',
            global: false,
            // async: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'refreshEPList' !<br>Votre installation semble corrompue.<br>"+error);
                document.getElementById("idEPList").value = "ERREUR";
                document.getElementById("idStatus").value = "ERREUR 'End Points'";
            },
            success: function (json_res) {
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    console.log("error="+res.error);
                    document.getElementById("idEPList").value = "ERREUR";
                    document.getElementById("idStatus").value = "ERREUR 'End Points'";
                } else {
                    console.log("res.resp follows:");
                    console.log(res.resp);
                    var resp = res.resp;

                    eq.epCount = resp.EPCount;
                    eq.epList = []; // Array of objects
                    for (epIdx = 0; epIdx < resp.EPCount; epIdx++) {

                        ep = new Object();
                        ep.id = resp.EPList[epIdx];
                        eq.epList.push(ep);
                    }

                    /* Updating display */
                    var endpoints = "";
                    for (epIdx = 0; epIdx < resp.EPCount; epIdx++) {
                        if (endpoints != "")
                            enpoints += ", ";
                        endpoints += resp.EPList[epIdx];

                        document.getElementById("idClustLab"+epIdx).innerHTML = "Clusters EP"+resp.EPList[epIdx]+":";
                        $("#idEP"+epIdx).show();
                    }
                    for (; epIdx < resp.EPCount; epIdx++) {
                        $("#idEP"+epIdx).hide();
                    }
                    document.getElementById("idEPList").value = endpoints;
                    document.getElementById("idStatus").value = "";
                }
            }
        });
    }

    /* Request manufacturer or model name.
       Returns: Ajax promise */
    function refreshXName(x) {
        console.log("refreshXName(x="+x+")");

        /* First of all, ensure eq is responding */
        // status = await waitAlive(10);
        // if ($status != 0)
        //     return;

        epNb = 1;
        if (x == "Manuf") {
            document.getElementById("idStatus").value = "Mise-à-jour du fabricant";
            var attrId = "0004";
            var field = document.getElementById("idZigbeeManuf");
        } else {
            document.getElementById("idStatus").value = "Mise-à-jour du modèle";
            var attrId = "0005";
            var field = document.getElementById("idZigbeeModel");
        }

        // return new Promise((resolve, reject) => {
        return $.ajax({
                type: 'POST',
                url: 'plugins/Abeille/core/ajax/AbeilleEqAssist.ajax.php',
                data: {
                    action: 'readAttributResponse',
                    zgNb: js_zgNb,
                    eqAddr: js_eqAddr,
                    eqEP: epNb, // EP number
                    clustId: "0000", // Basic cluster
                    attrId: attrId,
                },
                dataType: 'json',
                global: false,
                // async: false,
                error: function (request, status, error) {
                    console.log("refreshXName(x="+x+") ERROR: "+error);
                    bootbox.alert("ERREUR 'readAttributResponse' !<br>Votre installation semble corrompue.<br>"+error);
                    document.getElementById("idStatus").value = "ERREUR fabricant/modèle";
                    // reject();
                },
                success: function (json_res) {
                    res = JSON.parse(json_res.result);
                    if (res.status != 0) {
                        console.log("refreshXName(x="+x+") ERROR: "+res.error);
                        document.getElementById("idStatus").value = "ERREUR modèle";
                        // reject();
                    } else {
                        console.log("res.resp follows:");
                        console.log(res.resp);
                        var resp = res.resp;
                        var attr = resp.Attributes[0];
                        if (attr.Status != "00")
                            field.value = "-- Non supporté --";
                        else
                            field.value = attr.Data;

                        document.getElementById("idStatus").value = "";
                        console.log("refreshXName(x="+x+") => "+field.value);
                        // resolve();
                    }
                }
            });
        // });
    }

    /* "ping" equipment by requesting ZCLVersion (clust 0000, attr 0000).
       Returns: 0=OK, -1=ERROR */
    function pingEQ(x) {
        console.log("pingEQ(x="+x+")");

        epNb = 1;
        document.getElementById("idStatus").value = "Pinging";
        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleEqAssist.ajax.php',
            data: {
                action: 'readAttributResponse',
                zgNb: js_zgNb,
                eqAddr: js_eqAddr,
                eqEP: epNb, // EP number
                clustId: "0000", // Basic cluster
                attrId: "0000", // ZCLVersion
            },
            dataType: 'json',
            global: false,
            async: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'readAttributResponse' !<br>Votre installation semble corrompue.<br>"+error);
                status = -1;
            },
            success: function (json_res) {
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    console.log("error="+res.error);
                } else {
                    console.log("res.resp follows:");
                    console.log(res.resp);

                    status = 0;
                }
            }
        });
        return status;
    }

    /* Ping EQ until timeout is reached.
       Returns: 0=OK (alive), -1=ERROR (timeout) */
    async function waitAlive(timeout) {
        status = await pingEQ();
        if (status != 0) {
            var interv = setInterval(function(){ t++; }, 1000);
            // TODO: Need an async popup
            // alert("Cet équipement ne répond pas. Veuillez le reveiller");
            for (var t = 0; (status != 0) && (t < timeout); ) {
                status = await pingEQ();
            }
            clearInterval(interv);
        }

        return status;
    }

    function printbidon() {
        console.log("printbidon()");
    }

    /* Refresh ALL fields */
    async function interrogateEq() {
        console.log("interrogateEq(), zgNb="+js_zgNb+", eqAddr="+js_eqAddr);

        /* First of all, ensure eq is responding */
        // status = await waitAlive(10);

        $.when(refreshXName("Manuf"))
        .then(refreshXName("Model"))
        .then(refreshEPList())
        .then(function(value) {
    console.log("Successful ajax call, but no data was returned");
});
        /* Refreshing everything in order */
        // refreshXName("Manuf")
        // .then(console.log("c est fini"));
        // refreshXName("Manuf").done(printbidon());
        // refreshXName("Manuf")
        // .then(refreshXName("Model"))
        // .then(refreshEPList());
        // promiseManuf.then(
        //     promiseModel = refreshXName("Model");
        //     promiseModel.then(
        //         promiseEPList = refreshEPList();
        //     );
        // );

//         if (status == 0)
//             status = await refreshEPList();
//         console.log("la status="+status);
//         console.log("la eq.EPCount="+eq.epCount);
//         if (status == 0) {
//             for (epIdx = 0; (status == 0) && (epIdx < eq.epCount); epIdx++) {
//                 $status = await refreshClustersList(epIdx);
//             }
//         }
//         console.log("la2 status="+status);
//         if (status == 0) {
//             for (epIdx = 0; (status == 0) && (epIdx < eq.epCount); epIdx++) {
//                 ep = eq.epList[epIdx];
//                 console.log("la3 ep.inClustCount="+ep.inClustCount);
// console.log("la4 ep follows");
// console.log(ep);
//                 for (clustIdx = 0; (status == 0) && (clustIdx < ep.inClustCount); clustIdx++) {
//                     $status = await refreshAttributsList(epIdx, 0, clustIdx, 0);
//                 }
//                 for (clustIdx = 0; (status == 0) && (clustIdx < ep.outClustCount); clustIdx++) {
//                     $status = await refreshAttributsList(epIdx, 1, clustIdx, 0);
//                 }
//             }
//         }
    }


    /* Reminder
    var eq = new Object(); // Equipement details
    eq.zgNb = js_zgNb; // Zigate number, number
    eq.id = js_eqId; // Jeedom ID, number
    eq.addr = js_eqAddr; // Short addr, hex string
    eq.epCount = 0; // Number of EP, number
    eq.epList = new Array(); // Array of objects
        // ep = eq.epList[epIdx] = new Object(); // End Point object
        // ep.id = 0; // EP id/number
        // ep.inClustCount = 0; // IN clusters count
        // ep.inClustList = new Array();
        // ep.outClustCount = 0; // OUT clusters count
        // ep.outClustList = new Array();
        //     clust = new Object();
        //     clust.id = "0000"; // Cluster id, hex string
        //     clust.attrList = new Array(); // Attributs for this cluster
        //         a = new Object(); // Attribut object
        //         a.type = "00"; // Attribut type, hex string
        //         a.id = "0000"; // Attribut id, hex string
    */

    function prepareJson() {
        console.log("prepareJson()");

        /* Converting detected attributs to commands */
        var z = {
            "0000": { // Basic cluster
                "0000" : { "name" : "ZCLVersion", "type" : "R" },
                "0004" : { "name" : "ManufacturerName", "type" : "R" },
                "0005" : { "name" : "ModelIdentifier", "type" : "R" },
                "0006" : { "name" : "DateCode", "type" : "R" },
                "0007" : { "name" : "PowerSource", "type" : "R" },
            },
            "0003": { // Identify cluster
                "0000" : { "name" : "IdentifyTime", "type" : "RW" },
                "cmd1" : { "name" : "Identify" },
                "cmd2" : { "name" : "IdentifyQuery" },
                "cmd3" : { "name" : "TriggerEffect" },
            },
            "0004": { // Groups cluster
                // Attributes
                "0000" : { "name" : "NameSupport", "type" : "R" },
                // Cmds
                "cmd1" : { "name" : "AddGroup" },
                "cmd2" : { "name" : "ViewGroup" },
                "cmd3" : { "name" : "GetGroupMembership" },
                "cmd4" : { "name" : "RemoveGroup" },
                "cmd5" : { "name" : "RemoveAllGroups" },
                "cmd6" : { "name" : "AddGroupIfIdent" },
            },
            // "0005": { // Scene cluster
            //     "0000" : "zbNameSupport",
            // }
            "0006": { // On/Off cluster
                "0000" : { "name" : "OnOff", "type" : "R" },
                "4000" : { "name" : "GlobalSceneControl", "type" : "R" },
                "4001" : { "name" : "OnTime", "type" : "RW" },
                "4002" : { "name" : "OffWaitTime", "type" : "RW" },
                "cmd1" : { "name" : "Off" },
                "cmd2" : { "name" : "On" },
                "cmd3" : { "name" : "Toggle" },
            },
            "0007": { // On/Off switch config cluster
                "0000" : { "name" : "SwitchType", "type" : "R" },
                "0010" : { "name" : "SwitchActions", "type" : "RW" },
            },
            "0008": { // Level control cluster
                "0000" : { "name" : "CurrentLevel", "type" : "R" },
                "0001" : { "name" : "RemainingTime", "type" : "R" },
                "0010" : { "name" : "OnOffTransitionTime", "type" : "RW" },
                "0011" : { "name" : "OnLevel", "type" : "RW" },
                "0012" : { "name" : "OnTransitionTime", "type" : "RW" },
                "0013" : { "name" : "OffTransitionTime", "type" : "RW" },
                "0014" : { "name" : "DefaultMoveRate", "type" : "RW" },
                "cmd1" : { "name" : "MoveToLevel" },
                "cmd2" : { "name" : "Move" },
                "cmd3" : { "name" : "Step" },
                "cmd4" : { "name" : "Stop" },
                "cmd5" : { "name" : "MoveToLevelWithOnOff" },
                "cmd6" : { "name" : "MoveWithOnOff" },
                "cmd7" : { "name" : "StepWithOnOff" },
                // "cmd8" : { "name" : "Stop" }, // Another "stop" (0x07) ?
            },
            "0009": { // Alarm cluster
                "0000" : { "name" : "AlarmCount", "type" : "R" },
                "cmd1" : { "name" : "ResetAlarm" },
                "cmd2" : { "name" : "ResetAllAlarms" },
                "cmd3" : { "name" : "GetAlarm" },
                "cmd4" : { "name" : "ResetAlarmLog" },
            },
            "000A": { // Time cluster
                "0000" : { "name" : "Time", "type" : "RW" },
                "0001" : { "name" : "TimeStatus", "type" : "RW" },
                "0002" : { "name" : "TimeZone", "type" : "RW" },
                "0003" : { "name" : "DstStart", "type" : "RW" },
                "0004" : { "name" : "DstEnd", "type" : "RW" },
                "0005" : { "name" : "DstShift", "type" : "RW" },
                "0006" : { "name" : "StandardTime", "type" : "R" },
                "0007" : { "name" : "LocalTime", "type" : "R" },
                "0008" : { "name" : "LastSetTime", "type" : "R" },
                "0009" : { "name" : "ValidUntilTime", "type" : "RW" },
                // No cmds
            },
            "0020": { // Poll control cluster
                "0000" : { "name" : "CheckInInterval", "type" : "RW" },
                "0001" : { "name" : "LongPollInterval", "type" : "R" },
                "0002" : { "name" : "ShortPollInterval", "type" : "R" },
                "0003" : { "name" : "FastPollTimeout", "type" : "RW" },
                "0004" : { "name" : "CheckInIntervalMin", "type" : "R" },
                "0005" : { "name" : "LongPollIntervalMin", "type" : "R" },
                "0006" : { "name" : "FastPollTimeoutMax", "type" : "R" },
                "cmd1" : { "name" : "CheckIn" },
            },
            "0102": { // Window covering cluster
                // Information attributes
                "0000" : { "name" : "WindowCoveringType", "type" : "R" },
                "0001" : { "name" : "PhysClosedLimitLift", "type" : "R" },
                "0002" : { "name" : "PhysClosedLimitTilt", "type" : "R" },
                "0003" : { "name" : "CurPosLift", "type" : "R" },
                "0004" : { "name" : "CurPosTilt", "type" : "R" },
                "0005" : { "name" : "NbOfActuationsLift", "type" : "R" },
                "0006" : { "name" : "NbOfActuationsTilt", "type" : "R" },
                "0007" : { "name" : "ConfigStatus", "type" : "R" },
                "0008" : { "name" : "CurPosLiftPercent", "type" : "R" },
                "0009" : { "name" : "CurPosTiltPercent", "type" : "R" },
                // Settings attributes
                "0010" : { "name" : "InstalledOpenLimitLift", "type" : "R" },
                "0011" : { "name" : "InstalledClosedLimitLift", "type" : "R" },
                "0012" : { "name" : "InstalledOpenLimitTilt", "type" : "R" },
                "0013" : { "name" : "InstalledClosedLimitTilt", "type" : "R" },
                "0014" : { "name" : "VelocityLift", "type" : "RW" },
                "0015" : { "name" : "AccelTimeLift", "type" : "RW" },
                "0016" : { "name" : "DecelTimeLift", "type" : "RW" },
                "0017" : { "name" : "Mode", "type" : "RW" },
                "0018" : { "name" : "IntermSetpointsLift", "type" : "RW" },
                "0019" : { "name" : "IntermSetpointsTilt", "type" : "RW" },
                // Cmds
                "cmd1" : { "name" : "UpOpen" },
                "cmd2" : { "name" : "DownClose" },
                "cmd3" : { "name" : "Stop" },
                "cmd4" : { "name" : "GotoLiftVal" },
                "cmd5" : { "name" : "GotoLiftPercent" },
                "cmd6" : { "name" : "GotoTiltVal" },
                "cmd7" : { "name" : "GotoTiltPercent" },
            },
            "1000": { // Touchlink commissioning cluster
                // No attributes in this cluster
                // Cmds
                "cmd1" : { "name" : "ScanRequest" },
                "cmd2" : { "name" : "DevInfoReq" },
                "cmd3" : { "name" : "IdentifyReq" },
                "cmd4" : { "name" : "ResetToFactoryReq" },
                "cmd5" : { "name" : "NetworkStartReq" },
                "cmd6" : { "name" : "NetworkJoinRouterReq" },
                "cmd7" : { "name" : "NetworkJoinEndDeviceReq" },
                "cmd8" : { "name" : "NetworkUpdateReq" },
                "cmd9" : { "name" : "GetGroupIdReq" },
                "cmd10" : { "name" : "GetEPListReq" },
            }
        };
        var cmds = new Object();
        var cmdNb = 0;
        for (var epIdx = 0; epIdx < eq.epList.length; epIdx++) {
            ep = eq.epList[epIdx];
            console.log("EP"+ep.id+" (idx="+epIdx+")");

            for (var clustIdx = 0; clustIdx < ep.inClustList.length; clustIdx++) {
                clust = ep.inClustList[clustIdx];
                // console.log("IN clustId="+clust.id);

                if (!(clust.id in z)) {
                    console.log("IN cluster ID "+clust.id+" unknown");
                    continue;
                }
                zClust = z[clust.id];
                console.log("clustId="+clust.id);

                // console.log("clust.attrList.length="+clust.attrList.length);
                for (var attrIdx = 0; attrIdx < clust.attrList.length; attrIdx++) {
                    attr = clust.attrList[attrIdx];
                    console.log("attrId="+attr.id);

                    if (attr.id in zClust) {
                        /* Adding attributes access commands */
                        zAttr = zClust[attr.id];
                        if ((zAttr["type"] == "R") || (zAttr["type"] == "RW")) {
                            cmds['include'+cmdNb] = "zbGet"+zAttr["name"];
                            cmdNb++;
                            cmds['include'+cmdNb] = "zb"+zAttr["name"];
                            cmdNb++;
                        }
                        if ((zAttr["type"] == "W") || (zAttr["type"] == "RW")) {
                            cmds['include'+cmdNb] = "zbSet"+zAttr["name"];
                            cmdNb++;
                        }
                    } else {
                        console.log("Attr ID "+attr.id+" unknown in cluster ID "+clust.id);
                    }

                    /* Adding cluster specific commands */
                    if ("cmd1" in zClust) {
                        zCmdNb = 1;
                        zCmd = "cmd1";
                        while(zCmd in zClust) {
                            cmds['include'+cmdNb] = "zbCmd"+zClust[zCmd]["name"];
                            cmdNb++;

                            zCmdNb++;
                            zCmd = "cmd"+zCmdNb;
                        }
                    }
                }
            }
        }

        var jeq2 = new Object();
        // jeq2.nameJeedom = "";
        jeq2.timeout = document.getElementById("idTimeout").value;
        // jeq2.Comment = // Optional
        var cat = new Object();
        cat.automatism = 1;
        jeq2.Categorie = cat;
        // jeq2.configuration =
        jeq2.Commandes = cmds;
        var jeq = new Object();
        jeq[js_jsonName] = jeq2;

        return jeq;
    }

    /* Update/create JSON file */
    function writeJSON() {
        console.log("writeJSON()");

        // TODO: Check if infos missing before updating JSON
        /* Mandatory

            Timeout
         */

        jeq = prepareJson();

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleEqAssist.ajax.php',
            data: {
                action: 'writeConfigJson',
                jsonName: js_jsonName,
                eq: jeq,
            },
            dataType: 'json',
            global: false,
            // async: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'writeConfigJson' !<br>Votre installation semble corrompue.<br>"+error);
                status = -1;
            },
            success: function (json_res) {
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    console.log("error="+res.error);
                } else {
                }
            }
        });
    }

    /* Read JSON.
       Called from JSON read/reload button. */
    function readJSON() {
        console.log("readJSON()");

        /* TODO: Check if there is any user modification and ask user to cancel them */

        $.ajax({
            type: 'POST',
            url: 'plugins/Abeille/core/ajax/AbeilleFiles.ajax.php',
            data: {
                action: 'getFile',
                file: 'core/config/devices/'+js_jsonName+'/'+js_jsonName+'.json'
            },
            dataType: 'json',
            global: false,
            // async: false,
            error: function (request, status, error) {
                bootbox.alert("ERREUR 'readJSON' !<br>Votre installation semble corrompue.<br>"+error);
                status = -1;
            },
            success: function (json_res) {
                res = JSON.parse(json_res.result);
                if (res.status != 0) {
                    console.log("error="+res.error);
                } else {
                    console.log(res.content);
                    jeq = JSON.parse(res.content);
                    console.log(jeq);
                    jeq2 = jeq[js_jsonName];

                    /* Refresh display */
                    document.getElementById("idDesc").value = jeq2.nameJeedom;
                    document.getElementById("idTimeout").value = jeq2.timeout;
                    for (i = 0; i < js_categories.length; i++) {
                        cat = js_categories[i];
                        // console.log("cat="+cat);
                        jcat = jeq2.Categorie;
                        if (jcat[cat])
                            document.getElementById("id"+cat).checked = true;
                    }
                    document.getElementById("idIcon").value = jeq2.configuration.icone;
                    document.getElementById("idBattery").value = jeq2.configuration.battery_type;
                    jcmds = jeq2.Commandes; // JSON cmds
                    cmds = "";
                    for (const [key, value] of Object.entries(jcmds)) {
                        // "include3":"cmd12" => key="include3", value="cmd12"
                        console.log(`${key}: ${value}`);
                        cmds += '<div class="row">';
                        cmds += '<label class="col-lg-2 control-label" for="fname">'+key+':</label>';
                        cmds += '<div class="col-lg-10">';
                        cmds += '<input type="text" value="'+value+'" id="id'+key+'">';
                        cmds += '</div>';
                        cmds += '</div>';
                    }
                    $('#idCommands').empty().append(cmds);
                }
            }
        });
    }
</script>
