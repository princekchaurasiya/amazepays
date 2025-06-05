<?php
namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;
use Illuminate\Support\Facades\Http;

class CreateInvoiceAction extends AbstractAction
{
    public function getTitle()
    {
        return 'Create Invoice';
    }

    public function getIcon()
    {
        return 'voyager-receipt';
    }

    public function getPolicy()
    {
        return 'browse'; // or 'edit' based on your needs
    }

    public function getAttributes()
    {
        return [
            'class' => 'btn btn-sm btn-success',
        ];
    }

    public function getDefaultRoute()
    {
        return route('create_invoice', ['id' => $this->data->id]);
    }

    public function shouldActionDisplayOnRow($row)
    {
        return $this->dataType->slug === 'invoices'; // you can conditionally hide/show per row
    }
}
