import React from 'react';
import { File, FileText, FileVideo, FileImage, Download } from 'lucide-react';
import type { MessageAttachment } from '@/features/message';

interface MessageAttachmentsProps {
  attachments: MessageAttachment[];
}

export const MessageAttachments: React.FC<MessageAttachmentsProps> = ({ attachments }) => {
  if (!attachments || attachments.length === 0) {
    return null;
  }

  const getFileIcon = (fileType: string) => {
    if (fileType.startsWith('image/')) return <FileImage size={20} />;
    if (fileType.startsWith('video/')) return <FileVideo size={20} />;
    if (fileType.includes('pdf') || fileType.includes('document')) return <FileText size={20} />;
    return <File size={20} />;
  };

  const formatFileSize = (bytes: number): string => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  };

  const isImage = (fileType: string) => fileType.startsWith('image/');

  // Separate images and other files
  const images = attachments.filter(att => isImage(att.fileType));
  const files = attachments.filter(att => !isImage(att.fileType));

  return (
    <div className="message__attachments">
      {/* Image attachments */}
      {images.length > 0 && (
        <div className={`attachments-images ${images.length > 1 ? 'attachments-images--grid' : ''}`}>
          {images.map((attachment) => (
            <a
              key={attachment.id}
              href={attachment.fileUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="attachment-image"
            >
              <img
                src={attachment.thumbnailUrl || attachment.fileUrl}
                alt={attachment.fileName}
                loading="lazy"
              />
            </a>
          ))}
        </div>
      )}

      {/* File attachments */}
      {files.length > 0 && (
        <div className="attachments-files">
          {files.map((attachment) => (
            <a
              key={attachment.id}
              href={attachment.fileUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="attachment-file"
              download
            >
              <div className="attachment-file__icon">
                {getFileIcon(attachment.fileType)}
              </div>
              <div className="attachment-file__info">
                <div className="attachment-file__name">{attachment.fileName}</div>
                <div className="attachment-file__size">{formatFileSize(attachment.fileSize)}</div>
              </div>
              <div className="attachment-file__download">
                <Download size={16} />
              </div>
            </a>
          ))}
        </div>
      )}
    </div>
  );
};
