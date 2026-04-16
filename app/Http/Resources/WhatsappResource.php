<?php

namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class WhatsappResource extends JsonResource
{

    public $info;

    public function __construct($info)
    {
        parent::__construct($info);
        $this->info = $info;
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            "whatsapp_number"       => $this->info['whatsapp_number'],
            "whatsapp_status"       => $this->info['whatsapp_status'],
            "whatsapp_calling_code" => $this->info['whatsapp_calling_code'],
        ];
    }
}
