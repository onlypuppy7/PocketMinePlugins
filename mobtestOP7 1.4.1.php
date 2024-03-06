<?php

  /*
  __PocketMine Plugin__
  name=mobTest (FIXED)
  description=Create some NPCs!
  version=1.4
  author=zhuowei/onlypuppy7
  class=MobTest
  apiversion=11
  */

  /*
  Small Changelog
  ===============

  1.0: Initial release

  1.1: NPCs now chase you

  */


  class MobTest implements Plugin
  {

    private $api, $npclist, $config, $path;

    public function __construct(ServerAPI $api, $server = false)
    {
      $this->api              = $api;
      $this->npclist          = array();
    }

    public function init()
    {
      //$this->api->schedule(100, array($this, "tickHandler"), array(), true);
      $this->api->schedule(200, array($this,"spawnAllNpcs"), array(), true); //change to set mob spawn delay in ticks (seconds*20)
      $this->api->schedule(200, array($this,"spawnNightMobs"), array(), true); //LEAVE THIS ONE ALONE
     // $this->spawnAllNpcs();
      $alreadySpawned=false;
    }

    public function spawnAllNpcs()
    {
      //$npcplayer             = new Player("0", "127.0.0.1", 0, 0); //all NPC related packets are fired at localhost
     // $npcplayer->spawned    = true;
      $entitiesBefore=count($this->api->entity->getAll());
      $entityLimit = 100;
      if ($entitiesBefore <= 100) {
        for ($i=1; $i<=count($this->api->player->online()); $i++) {
          $type = rand(10, 13);
          $randomAreaX=rand(3,253);
          $randomAreaZ=rand(3,253);
          $entityit = $this->api->entity->add($this->api->level->getDefault(), ENTITY_MOB, $type, array(
            "x"      => $randomAreaX,
            "y"      => 80,
            "z"      => $randomAreaZ,
            "Health" => 10,
          ));
          $entityit->setName("Mob");
          $this->api->entity->spawnToAll($entityit, $this->api->level->getDefault());
          $entityit2 = $this->api->entity->add($this->api->level->getDefault(), ENTITY_MOB, $type, array(
            "x"      => $randomAreaX+2,
            "y"      => 80,
            "z"      => $randomAreaZ-1,
            "Health" => 10,
          ));
          $entityit2->setName("Mob");
          $this->api->entity->spawnToAll($entityit2, $this->api->level->getDefault());
          $entityit3 = $this->api->entity->add($this->api->level->getDefault(), ENTITY_MOB, $type, array(
            "x"      => $randomAreaX-1,
            "y"      => 80,
            "z"      => $randomAreaZ-1,
            "Health" => 10,
          ));
          $entityit3->setName("Mob");
          $this->api->entity->spawnToAll($entityit3, $this->api->level->getDefault());
          console("Spawned passive mobs at ".$randomAreaX.", ".$randomAreaZ);
        }
      }
      else{
        console("Entity limit reached! Passive mobs not spawned");
      }
      $entitiesNow=count($this->api->entity->getAll());
      console("New Mobs: ".($entitiesNow - $entitiesBefore).", Current Entity Count: ".count($this->api->entity->getAll()).", Entity Limit: ".$entityLimit.", Players online: ".count($this->api->player->online()));
      $this->api->chat->broadcast("Spawned ".($entitiesNow - $entitiesBefore)." mobs!");
    }

    public function spawnNightMobs()
    {
      //$npcplayer             = new Player("0", "127.0.0.1", 0, 0); //all NPC related packets are fired at localhost
     // $npcplayer->spawned    = true;
      //console("nightcheck...");
      $entitiesBefore=count($this->api->entity->getAll());
      global $alreadySpawned;
      $entityLimit = 100;
      if ((($this->api->time->getPhase())=="night") and ($alreadySpawned==false)) {
        $alreadySpawned=true;
        if ($entitiesBefore <= $entityLimit) {
          for ($i=1; $i<=((count($this->api->player->online()))*2); $i++) {
            $type = rand(1,3);
            if ($type==1) {
              $type=32;
            } elseif ($type==2) {
              $type==34;
            } elseif ($type==3) {
              $type==35;
            }
            $randomAreaX=rand(3,253);
            $randomAreaZ=rand(3,253);
            $entityit = $this->api->entity->add($this->api->level->getDefault(), ENTITY_MOB, $type, array(
              "x"      => $randomAreaX,
              "y"      => 80,
              "z"      => $randomAreaZ,
              "Health" => 10,
            ));
            $entityit->setName("Mob");
            $this->api->entity->spawnToAll($entityit, $this->api->level->getDefault());
            $entityit2 = $this->api->entity->add($this->api->level->getDefault(), ENTITY_MOB, $type, array(
              "x"      => $randomAreaX+2,
              "y"      => 80,
              "z"      => $randomAreaZ-1,
              "Health" => 10,
            ));
            $entityit2->setName("Mob");
            $this->api->entity->spawnToAll($entityit2, $this->api->level->getDefault());
            $entityit3 = $this->api->entity->add($this->api->level->getDefault(), ENTITY_MOB, $type, array(
              "x"      => $randomAreaX-1,
              "y"      => 80,
              "z"      => $randomAreaZ-1,
              "Health" => 10,
            ));
            $entityit3->setName("Mob");
            $this->api->entity->spawnToAll($entityit3, $this->api->level->getDefault());
            console("Spawned 'hostile' mobs at ".$randomAreaX.", ".$randomAreaZ);
            $this->api->entity->spawnToAll($entityit, $this->api->level->getDefault());
            $this->api->chat->broadcast("Looks like it's night time! Spawned ".((count($this->api->player->online()))*6)." night-time mobs!");
          }
        }
        else {
          console("Entity limit reached! Night mobs not spawned");
        }
        $entitiesNow=count($this->api->entity->getAll());
        console("New Mobs: ".($entitiesNow - $entitiesBefore).", Current Entity Count: ".count($this->api->entity->getAll()).", Entity Limit: ".$entityLimit.", Players online: ".count($this->api->player->online()));
      }
      elseif ((($this->api->time->getPhase())!="night") and ($alreadySpawned==true)) {
        $alreadySpawned=false;
      }
    }
    /*public function fireMoveEvent($entity)
    {
      if ($entity->speedX != 0) {
        $entity->x += $entity->speedX * 5;
      }
      if ($entity->speedY != 0) {
        $entity->y += $entity->speedY * 5;
      }
      if ($entity->speedZ != 0) {
        $entity->z += $entity->speedZ * 5;
      }
      if (($entity->last[0] != $entity->x or $entity->last[1] != $entity->y or $entity->last[2] != $entity->z or $entity->last[3] != $entity->yaw or $entity->last[4] != $entity->pitch)) {
        if ($this->api->handle("entity.move", $entity) === false) {
          $entity->setPosition($entity->last[0], $entity->last[1], $entity->last[2], $entity->last[3], $entity->last[4]);
        }
        $entity->updateLast();
      }
    } */


    public function __destruct()
    {

    }


  }


  //Rising, 18000, midday is 4500, midnight is around 15000, decline, I think is the evening? I think that is 10000