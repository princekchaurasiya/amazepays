<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class SendToCardPay extends AbstractAction
{
    public function getTitle()
    {
         \Log::info('CardPay Action Loaded');
        return 'Send to CardPay';
    }

    public function getIcon()
    {
        return 'voyager-upload'; // You can change this to another Voyager icon
    }

    public function getAttributes()
    {
        return [
            'class' => 'btn btn-sm btn-primary',
        ];
    }

    public function getDefaultRoute()
    {
        return route('send.transaction.report', ['id' => $this->data->id]);
    }

    public function shouldActionDisplayOnRow($row)
    {
        return $this->dataType->slug === 'transaction-reports'; // Always show the button
    }
}
