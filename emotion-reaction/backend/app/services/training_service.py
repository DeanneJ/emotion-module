"""
Training Service
Handles model training for both text and image emotion classifiers
"""

import torch
import torch.nn as nn
from torch.optim import AdamW
from torch.utils.data import DataLoader
from typing import Dict, List, Any, Optional, Callable
from pathlib import Path
import logging
import time
from tqdm import tqdm
import json


class TrainingService:
    """Unified training service for emotion classifiers"""
    
    def __init__(self, model, device: str = "cuda" if torch.cuda.is_available() else "cpu"):
        self.model = model
        self.device = torch.device(device)
        self.model.to(self.device)
        self.logger = logging.getLogger(__name__)
        
        # Training state
        self.current_epoch = 0
        self.best_val_loss = float('inf')
        self.best_val_f1 = 0.0
        self.training_history = []
    
    def train_epoch(self, train_loader: DataLoader, optimizer, criterion, 
                   progress_callback: Optional[Callable] = None) -> Dict[str, float]:
        """Train for one epoch"""
        self.model.train()
        total_loss = 0.0
        correct = 0
        total = 0
        
        pbar = tqdm(train_loader, desc=f"Epoch {self.current_epoch + 1}")
        
        for batch_idx, batch in enumerate(pbar):
            # Move batch to device
            if isinstance(batch, dict):
                inputs = {k: v.to(self.device) for k, v in batch.items() if k != 'labels'}
                labels = batch['labels'].to(self.device)
            else:
                # Assume (inputs, labels) tuple
                inputs, labels = batch
                if isinstance(inputs, dict):
                    inputs = {k: v.to(self.device) for k, v in inputs.items()}
                else:
                    inputs = inputs.to(self.device)
                labels = labels.to(self.device)
            
            # Forward pass
            optimizer.zero_grad()
            
            if isinstance(inputs, dict):
                outputs = self.model(**inputs)
            else:
                outputs = self.model(inputs)
            
            loss = criterion(outputs, labels)
            
            # Backward pass
            loss.backward()
            optimizer.step()
            
            # Update metrics
            total_loss += loss.item()
            
            # Calculate accuracy
            if len(outputs.shape) > 1 and outputs.shape[1] > 1:
                # Multi-class classification
                _, predicted = torch.max(outputs, 1)
                if len(labels.shape) > 1:
                    _, labels_idx = torch.max(labels, 1)
                else:
                    labels_idx = labels
                correct += (predicted == labels_idx).sum().item()
            else:
                # Binary classification
                predicted = (torch.sigmoid(outputs) > 0.5).float()
                correct += (predicted == labels).sum().item()
            
            total += labels.size(0)
            
            # Update progress bar
            pbar.set_postfix({
                'loss': f'{loss.item():.4f}',
                'acc': f'{100. * correct / total:.2f}%'
            })
            
            # Call progress callback
            if progress_callback:
                progress_callback(batch_idx, len(train_loader), loss.item(), correct / total)
        
        avg_loss = total_loss / len(train_loader)
        accuracy = correct / total
        
        return {
            'loss': avg_loss,
            'accuracy': accuracy
        }
    
    def validate(self, val_loader: DataLoader, criterion) -> Dict[str, float]:
        """Validate the model"""
        self.model.eval()
        total_loss = 0.0
        correct = 0
        total = 0
        
        all_predictions = []
        all_labels = []
        
        with torch.no_grad():
            for batch in tqdm(val_loader, desc="Validating"):
                # Move batch to device
                if isinstance(batch, dict):
                    inputs = {k: v.to(self.device) for k, v in batch.items() if k != 'labels'}
                    labels = batch['labels'].to(self.device)
                else:
                    inputs, labels = batch
                    if isinstance(inputs, dict):
                        inputs = {k: v.to(self.device) for k, v in inputs.items()}
                    else:
                        inputs = inputs.to(self.device)
                    labels = labels.to(self.device)
                
                # Forward pass
                if isinstance(inputs, dict):
                    outputs = self.model(**inputs)
                else:
                    outputs = self.model(inputs)
                
                loss = criterion(outputs, labels)
                total_loss += loss.item()
                
                # Calculate accuracy
                if len(outputs.shape) > 1 and outputs.shape[1] > 1:
                    _, predicted = torch.max(outputs, 1)
                    if len(labels.shape) > 1:
                        _, labels_idx = torch.max(labels, 1)
                    else:
                        labels_idx = labels
                    correct += (predicted == labels_idx).sum().item()
                    
                    all_predictions.extend(predicted.cpu().numpy())
                    all_labels.extend(labels_idx.cpu().numpy())
                else:
                    predicted = (torch.sigmoid(outputs) > 0.5).float()
                    correct += (predicted == labels).sum().item()
                    
                    all_predictions.extend(predicted.cpu().numpy())
                    all_labels.extend(labels.cpu().numpy())
                
                total += labels.size(0)
        
        avg_loss = total_loss / len(val_loader)
        accuracy = correct / total
        
        # Calculate F1 score
        try:
            from sklearn.metrics import f1_score
            f1 = f1_score(all_labels, all_predictions, average='weighted')
        except:
            f1 = 0.0
        
        return {
            'loss': avg_loss,
            'accuracy': accuracy,
            'f1_score': f1
        }
    
    def train(self, train_loader: DataLoader, val_loader: DataLoader,
             num_epochs: int, learning_rate: float = 1e-4,
             save_dir: Optional[Path] = None,
             progress_callback: Optional[Callable] = None) -> Dict[str, Any]:
        """Full training loop"""
        
        # Setup optimizer and loss
        optimizer = AdamW(self.model.parameters(), lr=learning_rate)
        criterion = nn.BCEWithLogitsLoss()  # Works for both binary and multi-class
        
        # Training loop
        for epoch in range(num_epochs):
            self.current_epoch = epoch
            
            self.logger.info(f"\nEpoch {epoch + 1}/{num_epochs}")
            
            # Train
            train_metrics = self.train_epoch(train_loader, optimizer, criterion, progress_callback)
            
            # Validate
            val_metrics = self.validate(val_loader, criterion)
            
            # Log metrics
            self.logger.info(f"Train Loss: {train_metrics['loss']:.4f}, Acc: {train_metrics['accuracy']:.4f}")
            self.logger.info(f"Val Loss: {val_metrics['loss']:.4f}, Acc: {val_metrics['accuracy']:.4f}, F1: {val_metrics['f1_score']:.4f}")
            
            # Save history
            self.training_history.append({
                'epoch': epoch + 1,
                'train_loss': train_metrics['loss'],
                'train_accuracy': train_metrics['accuracy'],
                'val_loss': val_metrics['loss'],
                'val_accuracy': val_metrics['accuracy'],
                'val_f1': val_metrics['f1_score']
            })
            
            # Save best model
            if save_dir and val_metrics['f1_score'] > self.best_val_f1:
                self.best_val_f1 = val_metrics['f1_score']
                save_path = Path(save_dir) / 'best_model.pth'
                torch.save({
                    'epoch': epoch + 1,
                    'model_state_dict': self.model.state_dict(),
                    'optimizer_state_dict': optimizer.state_dict(),
                    'val_f1': self.best_val_f1,
                    'val_loss': val_metrics['loss']
                }, save_path)
                self.logger.info(f"✅ Saved best model to {save_path}")
            
            # Save checkpoint every 5 epochs
            if save_dir and (epoch + 1) % 5 == 0:
                checkpoint_path = Path(save_dir) / f'checkpoint_epoch_{epoch + 1}.pth'
                torch.save({
                    'epoch': epoch + 1,
                    'model_state_dict': self.model.state_dict(),
                    'optimizer_state_dict': optimizer.state_dict(),
                    'val_f1': val_metrics['f1_score'],
                    'val_loss': val_metrics['loss']
                }, checkpoint_path)
        
        # Save training history
        if save_dir:
            history_path = Path(save_dir) / 'training_history.json'
            with open(history_path, 'w') as f:
                json.dump(self.training_history, f, indent=2)
        
        return {
            'best_val_f1': self.best_val_f1,
            'history': self.training_history
        }
