<?php  //$Id: filtersettings.php,v 1.1.2.2 2007/12/19 17:38:49 skodak Exp $

$settings->add(new admin_setting_configcheckbox('filter_ensemble_enable', get_string('enable_ensemble','filter_ensemble'), '', 0));

$settings->add(new admin_setting_configtext('filter_ensemble_url',
                   get_string('ensemble_url_prompt','filter_ensemble'),
                   get_string('ensemble_url_desc','filter_ensemble'),
                   'https://ensemble.illinois.edu'));
?>
