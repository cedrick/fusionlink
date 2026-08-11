<?php

class HomeController
{
    public function index()
    {
        View::render('home', [
            'title' => 'ISP Billing Dashboard'
        ]);
    }
}
