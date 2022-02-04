<?php

/** @var \Icinga\Application\Modules\Module $this */

$this->menuSection(N_('History'))->add(N_('Status Snapshots'))->setUrl('idosnap/snapshots')->setPriority(100);
