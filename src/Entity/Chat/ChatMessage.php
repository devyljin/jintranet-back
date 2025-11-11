<?php

namespace App\Entity\Chat;

use App\Entity\Traits\StatisticsPropertiesTrait;
use App\Repository\Chat\ChatMessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ChatMessageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ChatMessage
{
    use StatisticsPropertiesTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["chatChannel"])]
    private ?int $id = null;

    #[ORM\Column(length: 1000)]
    #[Groups(["chatChannel"])]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ChatChannel $channel = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Groups(["chatChannel"])]
    private ?ChatChannel $subChannel = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getChannel(): ?ChatChannel
    {
        return $this->channel;
    }

    public function setChannel(?ChatChannel $channel): static
    {
        $this->channel = $channel;

        return $this;
    }

    public function getSubChannel(): ?ChatChannel
    {
        return $this->subChannel;
    }

    public function setSubChannel(?ChatChannel $subChannel): static
    {
        $this->subChannel = $subChannel;

        return $this;
    }


}
