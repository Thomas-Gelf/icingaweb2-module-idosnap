<?php

namespace Icinga\Module\Idosnap\Controllers;

use gipfl\IcingaWeb2\CompatController;
use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Url;
use gipfl\Web\Widget\Hint;
use Icinga\Module\Idosnap\Db\Schema;
use Icinga\Module\Idosnap\SnapshotDiff;
use Icinga\Module\Idosnap\Web\Form\DeleteForm;
use Icinga\Module\Idosnap\Web\Form\ShowDiffForm;
use Icinga\Module\Idosnap\Web\Form\SnapshotForm;
use Icinga\Module\Idosnap\Web\Table\DiffTable;
use Icinga\Module\Idosnap\Web\Table\StatusTable;
use Icinga\Web\Notification;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class SnapshotController extends CompatController
{
    use Db;

    public function indexAction()
    {
        $this->addSingleTab($this->translate('Snapshot'));
        $uuid = $this->params->get('uuid');
        if ($uuid === null) {
            $this->create();
        } else {
            $this->show(Uuid::fromString($uuid));
        }
    }

    /**
     * @throws \Icinga\Exception\MissingParameterException
     */
    public function diffAction()
    {
        $this->addSingleTab($this->translate('Diff'));
        $leftUuid = Uuid::fromString($this->params->getRequired('left'));
        $rightUuid = Uuid::fromString($this->params->getRequired('right'));
        $leftSnap = $this->fetchSnapshot($leftUuid);
        $rightSnap = $this->fetchSnapshot($rightUuid);
        if ($leftSnap->ts_created > $rightSnap->ts_created) {
            $this->redirectNow($this->url()->with([
                'left' => $rightUuid->toString(),
                'right' => $leftUuid->toString(),
            ]));
        }

        $leftLabel = $leftSnap->label;
        $rightLabel = $rightSnap->label;
        $this->actions()->add(Link::create($leftLabel, 'idosnap/snapshot', [
            'uuid' => $leftUuid->toString()
        ], [
            'class' => 'icon-th-list'
        ]))->add(Link::create($rightLabel, 'idosnap/snapshot', [
            'uuid' => $rightUuid->toString()
        ], [
            'class' => 'icon-th-list'
        ]));
        $this->addTitle(sprintf($this->translate('State changes: %s -> %s'), $leftLabel, $rightLabel));
        $diff = new SnapshotDiff($leftUuid, $rightUuid, $this->getDb());
        $table = new DiffTable($this->getDb(), $diff);
        if (count($table) === 0) {
            $this->content()->add(Hint::info(
                $this->translate("Monitoring state didn't change between these snapshots")
            ));
        } else {
            $table->renderTo($this);
        }
    }

    protected function create()
    {
        $this->addTitle($this->translate('Create a new IDO Status Snapshot'));
        $form = new SnapshotForm($this->getIdoDb(), $this->getDb());
        $form->on($form::ON_SUCCESS, function (SnapshotForm $form) {
            $uuid = $form->getUuid();
            Notification::success($this->translate('Snapshot has been created'));
            $this->redirectNow(Url::fromPath('idosnap/snapshot', [
                'uuid' => $uuid->toString()
            ]));
        });
        $form->handleRequest($this->getServerRequest());
        $this->content()->add($form);
    }

    protected function show(UuidInterface $uuid)
    {
        $snapshot = $this->fetchSnapshot($uuid);
        $this->addTitle(sprintf($this->translate('Snapshot: %s'), $snapshot->label));
        $table = new StatusTable($this->getDb(), $uuid);
        $diff = new ShowDiffForm($this->getDb(), $uuid);
        $diff->on($diff::ON_SUCCESS, function (ShowDiffForm $form) use ($uuid) {
            $this->redirectNow(Url::fromPath('idosnap/snapshot/diff', [
                'left'  => $uuid->toString(),
                'right' => $form->getUuid()->toString()
            ]));
        })->handleRequest($this->getServerRequest());
        $delete = new DeleteForm($this->getDb(), $uuid);
        $delete->on($delete::ON_SUCCESS, function () {
            Notification::success($this->translate('Snapshot has been deleted'));
            $this->redirectNow(Url::fromPath('idosnap/snapshots'));
        })->handleRequest($this->getServerRequest());
        $this->actions()->add([$diff, $delete]);
        $table->renderTo($this);
    }

    protected function fetchSnapshot(UuidInterface $uuid)
    {
        $db = $this->getDb();
        return $db->fetchRow($db->select()->from(Schema::TABLE_SNAPSHOTS)->where('uuid = ?', $uuid->getBytes()));
    }
}
