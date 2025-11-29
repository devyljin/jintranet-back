<?php

namespace App\DataFixtures;

use App\Entity\Chat\ChatChannel;
use App\Entity\Chat\ChatMessage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ChatFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
//        for($i = 0; $i < 10; ++$i){
//            $channel = new ChatChannel();
//            $channel
//                ->setName("ChannelName#" . $i)
//                ->setVisibility("public")
//            ;
//
//            for($j = 0; $j < 10; ++$j)
//            {
//                $message = new ChatMessage();
//                $message->setContent("Yoyo" . $j);
//                $message->setChannel($channel);
//                $manager->persist($message);
//            }
//            $manager->persist($channel);
//        }
//
//
//        $manager->flush();
    }
}
