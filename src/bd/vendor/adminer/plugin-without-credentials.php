<?php

class PluginWithoutCredentials
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