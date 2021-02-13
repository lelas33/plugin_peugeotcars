<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('peugeotcars');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
   <div class="col-xs-12 eqLogicThumbnailDisplay">
        <legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
            <div class="cursor eqLogicAction logoPrimary" data-action="add">
                <i class="fas fa-plus-circle"></i>
                <br>
                <span>{{Ajouter}}</span>
            </div>
            <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
                <i class="fas fa-wrench"></i>
                <br>
                <span>{{Configuration}}</span>
            </div>
            <div class="cursor eqLogicAction logoSecondary" onclick="window.open('https:\/\/github.com/lelas33/plugin_peugeotcars/blob/master/README.md', '_blank')">
                <i class="fas fa-book"></i>
                <br>
                <span>{{Documentation}}</span>
            </div>
        </div>
        <legend><i class="fas fa-table"></i> {{Mes voitures}}</legend>
        <input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
        <div class="eqLogicThumbnailContainer">
            <?php
            foreach ($eqLogics as $eqLogic) {
                $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
                echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
                echo '<br>';
                echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
    <div class="col-xs-12 eqLogic" style="display: none;">
	    <div class="input-group pull-right" style="display:inline-flex">
            <span class="input-group-btn">
                <a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
            </span>
        </div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <form class="form-horizontal">
                    <fieldset>
                        <legend>
                           <i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}}
                           <i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i>
                       </legend>
			                 <div class="form-group">
                            <label class="col-lg-2 control-label">{{Nom de l'équipement}}</label>
                            <div class="col-lg-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de la voiture}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">{{Numéro du véhicule}}</label>
                            <div class="col-lg-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="logicalId" placeholder="{{VIN du véhicule}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label" >{{Objet parent}}</label>
                            <div class="col-lg-3">
                                <select class="form-control eqLogicAttr" data-l1key="object_id">
                                    <option value="">{{Aucun}}</option>
                                    <?php
                                    foreach (jeeObject::all() as $object) {
                                        echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>'."\n";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">{{Catégorie}}</label>
                            <div class="col-lg-8">
                                <?php
                                foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                    echo '<label class="checkbox-inline">'."\n";
                                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                    echo '</label>'."\n";
                                }
                                ?>

                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label" >{{Activer}}</label>
                            <div class="col-md-1">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>
                            </div>
                            <label class="col-lg-2 control-label" >{{Visible}}</label>
                            <div class="col-lg-1">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label"></label>
                            <label class="col-lg-3"><br>{{Informations complémentaires sur le véhicule}}</label>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">{{Capacité de la batterie (kWh)}}</label>
                            <div class="col-lg-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="batt_capacity" title="{{50 kWh pour une peugeot e-208 ou e-2008, 13.2 kWh pour une peugeot 3008 Hybride}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">{{Tension nominale de la batterie (V)}}</label>
                            <div class="col-lg-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="batt_nominal_voltage" title="{{400 V pour une peugeot e-208 ou e-2008, 300 V pour une peugeot 3008 Hybride}}"/>
                            </div>
                        </div>
                    </fieldset>
                </form>
			</div>
			<div role="tabpanel" class="tab-pane" id="commandtab">
                <table id="table_cmd" class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 230px;">{{Nom}}</th>
                            <th style="width: 110px;">{{Sous-Type}}</th>
                            <th style="width: 100px;">{{Paramètres}}</th>
                            <th style="width: 200px;"></th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
		   </div>
		</div>
    </div>
</div>

<?php include_file('desktop', 'peugeotcars', 'js', 'peugeotcars'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
