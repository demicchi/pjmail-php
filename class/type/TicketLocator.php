<?php

namespace StudioDemmys\pjmail\type;

class TicketLocator
{
    public function __construct(
        public ?string $project = null,
        public ?string $ticket = null,
    ){}
}