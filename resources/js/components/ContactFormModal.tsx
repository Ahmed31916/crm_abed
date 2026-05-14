import React from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { toast } from 'sonner';

interface ContactFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  businessId: number;
  businessName: string;
  themeColors?: any;
  themeFont?: string;
}

export default function ContactFormModal({ isOpen, onClose, businessName, themeColors }: ContactFormModalProps) {
  // Apply theme styles
  const modalStyle = themeColors ? {
    zIndex: 100001,
    backgroundColor: themeColors.background || '#ffffff',
    color: themeColors.text || '#000000',
    fontFamily: themeFont || 'inherit',
    border: `1px solid ${themeColors.borderColor || themeColors.primary || '#e2e8f0'}`
  } : {};

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    toast.success('Contact functionality has been removed from this application.');
    onClose();
  };

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="sm:max-w-md" style={modalStyle}>
        <DialogHeader>
          <DialogTitle className="text-xl font-semibold" style={{ color: themeColors?.text || 'inherit' }}>Contact {businessName}</DialogTitle>
        </DialogHeader>
        
        <div className="p-4 text-center">
          <p>The contact functionality has been removed from this application.</p>
          <Button onClick={onClose} className="mt-4">Close</Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}