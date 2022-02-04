<?php

namespace Icinga\Module\Idosnap\Web\Table;

use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use gipfl\ZfDb\Exception\SelectException;
use Icinga\Module\Idosnap\SnapshotDiff;

class DiffTable extends ZfQueryBasedTable
{
    /** @var SnapshotDiff */
    protected $diff;
    /** @var StatusDiffRowRenderer */
    protected $rowRenderer;

    public function __construct($db, SnapshotDiff $diff)
    {
        parent::__construct($db);
        $this->getAttributes()->add('class', 'status-table');
        $this->diff = $diff;
        $this->rowRenderer = new StatusDiffRowRenderer();
    }

    protected function renderRow($row)
    {
        return $this->rowRenderer->render($row, $this);
    }

    /**
     * @throws SelectException
     */
    protected function prepareQuery()
    {
        return $this->diff->getStatusDiffQuery()->limit(50);
    }
}
