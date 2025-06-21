<?php

class PluginWithoutCredentials extends Adminer\Plugin
{
  function credentials()
  {
    return true;
  }
  
  function login()
  {
    return true;
  }
}