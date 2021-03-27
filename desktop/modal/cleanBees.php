<?php
    /* This file is part of Jeedom.
    *
    * Jeedom is free software: you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation, either version 3 of the License, or
    * (at your option) any later version.
    *
    * Jeedom is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    * GNU General Public License for more details.
    *
    * You should have received a copy of the GNU General Public License
    * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
    */
    if (!isConnect('admin')) {
        throw new Exception('{{401 - Accès non autorisé}}');
    }

    /* PHP to JS */
    echo '<script>';
    echo 'var js_zgPort = "'.$_GET['zgPort'].'";';
    echo 'var js_zgNb = '.$_GET['zgNb'].';';
    echo 'var js_log = "'.__DIR__.'/../../tmp/cleanBees.log";';
    echo '</script>';
?>
<div id='div_cleanBeesAlert' style="display: none;"></div>
<a class="btn btn-warning pull-right" data-state="1" id="bt_abeilleLogStopStart"><i class="fa fa-pause"></i> {{Pause}}</a>
<input class="form-control pull-right" id="in_abeilleLogSearch" style="width : 300px;" placeholder="{{Rechercher}}"/>
<br/><br/><br/>
<pre id='pre_cleanBees' style='overflow: auto; height: 90%;with:90%;'>
Lancement de l'opération de nettoyage.
</pre>

<script>
    $.ajax({
        type: 'POST',
        url: 'plugins/Abeille/core/ajax/AbeilleClean.ajax.php',
        data: {
            action: 'cleanBees',
            zgPort: js_zgPort,
            zgNb: js_zgNb,
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
            handleAjaxError(request, status, error, $('#div_cleanBeesAlert'));
        },
        success: function () {
        }
    });

    function updatelog(){
        jeedom.log.autoupdate({
            log: js_log,
            display: $('#pre_cleanBees'),
            search: $('#in_abeilleLogSearch'),
            control: $('#bt_abeilleLogStopStart'),
        });
    }
    setTimeout(updatelog, 500);
</script>
