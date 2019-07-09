<?php

class Main extends Controller {
  public function index() {
    return View::create('index.php');
  }
}