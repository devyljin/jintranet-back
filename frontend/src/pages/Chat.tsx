import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { chatApi } from '../api/chat';
import type { ChatChannel } from '../types/index';

export default function Chat() {
  const [newChannelName, setNewChannelName] = useState('');
  const [selectedVisibility, setSelectedVisibility] = useState<'public' | 'private'>('public');
  const queryClient = useQueryClient();

  const { data: channels, isLoading, error } = useQuery({
    queryKey: ['channels'],
    queryFn: chatApi.getChannels,
  });

  const createChannelMutation = useMutation({
    mutationFn: chatApi.createChannel,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['channels'] });
      setNewChannelName('');
    },
  });

  const handleCreateChannel = (e: React.FormEvent) => {
    e.preventDefault();
    if (newChannelName.trim()) {
      createChannelMutation.mutate({
        name: newChannelName,
        visibility: selectedVisibility,
      });
    }
  };

  if (isLoading) return <div style={{ padding: '2rem' }}>Chargement...</div>;
  if (error) return <div style={{ padding: '2rem', color: 'red' }}>Erreur: {(error as Error).message}</div>;

  return (
    <div style={{ padding: '2rem' }}>
      <h1>Chat Channels</h1>

      <form onSubmit={handleCreateChannel} style={{ marginBottom: '2rem', padding: '1rem', border: '1px solid #ccc', borderRadius: '8px' }}>
        <h2>Créer un nouveau channel</h2>
        <div style={{ display: 'flex', gap: '1rem', marginTop: '1rem' }}>
          <input
            type="text"
            value={newChannelName}
            onChange={(e) => setNewChannelName(e.target.value)}
            placeholder="Nom du channel"
            style={{ flex: 1, padding: '0.5rem' }}
          />
          <select
            value={selectedVisibility}
            onChange={(e) => setSelectedVisibility(e.target.value as 'public' | 'private')}
            style={{ padding: '0.5rem' }}
          >
            <option value="public">Public</option>
            <option value="private">Privé</option>
          </select>
          <button type="submit" disabled={createChannelMutation.isPending} style={{ padding: '0.5rem 1rem' }}>
            {createChannelMutation.isPending ? 'Création...' : 'Créer'}
          </button>
        </div>
      </form>

      <div style={{ display: 'grid', gap: '1rem' }}>
        <h2>Channels disponibles ({channels?.length || 0})</h2>
        {channels && channels.length > 0 ? (
          channels.map((channel: ChatChannel) => (
            <div key={channel.id} style={{ border: '1px solid #ddd', padding: '1rem', borderRadius: '8px' }}>
              <h3>{channel.name}</h3>
              <div style={{ display: 'flex', gap: '1rem', fontSize: '0.9rem', color: '#666' }}>
                <span>Visibilité: {channel.visibility}</span>
                <span>Status: {channel.status}</span>
                {channel.parentChannel && <span>Parent: {channel.parentChannel.name}</span>}
              </div>
            </div>
          ))
        ) : (
          <p>Aucun channel disponible. Créez-en un!</p>
        )}
      </div>
    </div>
  );
}
