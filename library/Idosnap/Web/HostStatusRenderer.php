<?php

namespace Icinga\Module\Idosnap\Web;

use Icinga\Module\Idosnap\Severity;
use ipl\Html\BaseHtmlElement;

class HostStatusRenderer extends BaseHtmlElement
{
    /** @var Severity */
    protected $severity;

    /** @var string */
    protected $label;

    public function __construct(Severity $severity, $label = null)
    {
        $this->severity = $severity;
        $this->label = $label ?: $severity->getName();
    }

    protected function assemble()
    {
        $classes = [
            'badge',
            'status-' . $this->severity->getName()
        ];
        if ($this->severity->isHandled()) {
            $classes[] = 'handled';
        }
        $this->getAttributes()->add('class', $classes);
    }
}
