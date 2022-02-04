<?php

namespace Icinga\Module\Idosnap\Controllers;

use gipfl\IcingaWeb2\CompatController;
use gipfl\IcingaWeb2\Link;
use Icinga\Module\Idosnap\Web\Table\SnapshotsTable;

class SnapshotsController extends CompatController
{
    use Db;

    public function indexAction()
    {
        $this->addSingleTab($this->translate('Snapshots'));
        $this->addTitle($this->translate('IDO Status Snapshots'));
        if (false === ($db = $this->getDb())) {
            return;
        }
        $this->actions()->add(Link::create($this->translate('Create'), 'idosnap/snapshot', null, [
            'class'            => 'icon-plus',
            'data-base-target' => '_next'
        ]));

        $table = new SnapshotsTable($db);
        $table->renderTo($this);
    }
}
