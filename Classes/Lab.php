<?php

namespace SimpleLab;

require_once(DOC_ROOT . 'Classes/Company.php');
require_once(DOC_ROOT . 'Classes/Tickets.php');
require_once(DOC_ROOT . 'Classes/Ticket.php');
require_once(DOC_ROOT . 'Classes/Clients.php');
require_once(DOC_ROOT . 'Classes/Client.php');
require_once(DOC_ROOT . 'Classes/Users.php');

class Lab
{
    public $carrency, $lab,$company, $tickets, $users;

    function __construct()
    {
        $this->carrency = "₪";
        $this->company = new Company;
        $this->tickets = new Tickets;
        $this->users = new Users;
    }
}
