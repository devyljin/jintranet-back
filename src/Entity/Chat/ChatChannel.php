<?php

namespace App\Entity\Chat;

use App\Entity\Traits\StatisticsPropertiesTrait;
use App\Repository\Chat\ChatChannelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ChatChannelRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ChatChannel
{
    use StatisticsPropertiesTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["chatChannel"])]
    private ?int $id = null;

    #[Groups(["chatChannel"])]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 25)]
    private ?string $visibility = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parentChannel')]
    #[Groups(["chatChannel"])]
    private Collection $subChannels;

    /**
     * @var Collection<int, ChatMessage>
     */
    #[ORM\OneToMany(targetEntity: ChatMessage::class, mappedBy: 'channel', orphanRemoval: true)]
    #[Groups(["chatChannel"])]
    private Collection $messages;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subChannels')]
    private ?self $parentChannel = null;



    #[ORM\ManyToOne(inversedBy: 'subChannel')]
    private ?ChatMessage $parentMessage = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->subChannels = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(ChatMessage $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setChannel($this);
        }

        return $this;
    }

    public function removeMessage(ChatMessage $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getChannel() === $this) {
                $message->setChannel(null);
            }
        }

        return $this;
    }

    public function getParentChannel(): ?self
    {
        return $this->parentChannel;
    }

    public function setParentChannel(?self $parentChannel): static
    {
        $this->parentChannel = $parentChannel;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSubChannels(): Collection
    {
        return $this->subChannels;
    }

    public function addSubChannel(self $subChannel): static
    {
        if (!$this->subChannels->contains($subChannel)) {
            $this->subChannels->add($subChannel);
            $subChannel->setParentChannel($this);
        }

        return $this;
    }

    public function removeSubChannel(self $subChannel): static
    {
        if ($this->subChannels->removeElement($subChannel)) {
            // set the owning side to null (unless already changed)
            if ($subChannel->getParentChannel() === $this) {
                $subChannel->setParentChannel(null);
            }
        }

        return $this;
    }

    public function getParentMessage(): ?ChatMessage
    {
        return $this->parentMessage;
    }

    public function setParentMessage(?ChatMessage $parentMessage): static
    {
        $this->parentMessage = $parentMessage;

        return $this;
    }
}
