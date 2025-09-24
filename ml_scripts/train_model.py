#!/usr/bin/env python3
"""
AI-Powered IDS - Machine Learning Model Training Script
This script trains various ML models for intrusion detection.
"""

import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestClassifier
from sklearn.neural_network import MLPClassifier
from sklearn.svm import SVC
from sklearn.tree import DecisionTreeClassifier
from sklearn.naive_bayes import GaussianNB
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.metrics import classification_report, confusion_matrix, accuracy_score
import joblib
import os
import sys
import json
from datetime import datetime

class IDSModelTrainer:
    def __init__(self, data_path, model_output_dir):
        self.data_path = data_path
        self.model_output_dir = model_output_dir
        self.scaler = StandardScaler()
        self.label_encoder = LabelEncoder()
        
        # Ensure output directory exists
        os.makedirs(model_output_dir, exist_ok=True)
        
    def load_and_preprocess_data(self):
        """Load and preprocess the network traffic data"""
        print("Loading data...")
        
        # Try to load CSV data
        try:
            df = pd.read_csv(self.data_path)
        except Exception as e:
            print(f"Error loading data: {e}")
            # Create sample data for demonstration
            df = self.create_sample_data()
            
        print(f"Data shape: {df.shape}")
        
        # Basic preprocessing
        # Remove any rows with missing values
        df = df.dropna()
        
        # Separate features and target
        if 'label' in df.columns:
            X = df.drop('label', axis=1)
            y = df['label']
        elif 'attack_type' in df.columns:
            X = df.drop('attack_type', axis=1)
            y = df['attack_type']
        else:
            # If no clear target column, create one based on patterns
            X = df.select_dtypes(include=[np.number])
            y = self.create_synthetic_labels(X)
            
        # Encode categorical features
        for col in X.select_dtypes(include=['object']).columns:
            le = LabelEncoder()
            X[col] = le.fit_transform(X[col].astype(str))
            
        # Encode target labels
        y_encoded = self.label_encoder.fit_transform(y)
        
        # Scale features
        X_scaled = self.scaler.fit_transform(X)
        
        return X_scaled, y_encoded, list(self.label_encoder.classes_)
        
    def create_sample_data(self):
        """Create sample network traffic data for demonstration"""
        print("Creating sample data for demonstration...")
        
        np.random.seed(42)
        n_samples = 10000
        
        # Generate synthetic network features
        data = {
            'duration': np.random.exponential(2, n_samples),
            'protocol_type': np.random.choice(['tcp', 'udp', 'icmp'], n_samples),
            'service': np.random.choice(['http', 'ftp', 'smtp', 'ssh', 'telnet'], n_samples),
            'flag': np.random.choice(['SF', 'S0', 'REJ', 'RSTR'], n_samples),
            'src_bytes': np.random.exponential(1000, n_samples),
            'dst_bytes': np.random.exponential(1000, n_samples),
            'land': np.random.choice([0, 1], n_samples, p=[0.99, 0.01]),
            'wrong_fragment': np.random.poisson(0.1, n_samples),
            'urgent': np.random.poisson(0.05, n_samples),
            'hot': np.random.poisson(0.2, n_samples),
            'num_failed_logins': np.random.poisson(0.1, n_samples),
            'logged_in': np.random.choice([0, 1], n_samples, p=[0.3, 0.7]),
            'num_compromised': np.random.poisson(0.05, n_samples),
            'root_shell': np.random.choice([0, 1], n_samples, p=[0.95, 0.05]),
            'su_attempted': np.random.choice([0, 1], n_samples, p=[0.98, 0.02]),
            'num_root': np.random.poisson(0.1, n_samples),
            'num_file_creations': np.random.poisson(0.2, n_samples),
            'num_shells': np.random.poisson(0.1, n_samples),
            'num_access_files': np.random.poisson(0.15, n_samples),
            'count': np.random.poisson(10, n_samples),
            'srv_count': np.random.poisson(8, n_samples),
            'serror_rate': np.random.beta(1, 10, n_samples),
            'srv_serror_rate': np.random.beta(1, 10, n_samples),
            'rerror_rate': np.random.beta(1, 15, n_samples),
            'srv_rerror_rate': np.random.beta(1, 15, n_samples),
            'same_srv_rate': np.random.beta(8, 2, n_samples),
            'diff_srv_rate': np.random.beta(2, 8, n_samples),
            'srv_diff_host_rate': np.random.beta(1, 9, n_samples),
            'dst_host_count': np.random.poisson(50, n_samples),
            'dst_host_srv_count': np.random.poisson(20, n_samples),
            'dst_host_same_srv_rate': np.random.beta(8, 2, n_samples),
            'dst_host_diff_srv_rate': np.random.beta(2, 8, n_samples),
            'dst_host_same_src_port_rate': np.random.beta(5, 5, n_samples),
            'dst_host_srv_diff_host_rate': np.random.beta(1, 9, n_samples),
            'dst_host_serror_rate': np.random.beta(1, 10, n_samples),
            'dst_host_srv_serror_rate': np.random.beta(1, 10, n_samples),
            'dst_host_rerror_rate': np.random.beta(1, 15, n_samples),
            'dst_host_srv_rerror_rate': np.random.beta(1, 15, n_samples)
        }
        
        df = pd.DataFrame(data)
        
        # Create attack labels based on patterns
        attack_types = ['normal', 'dos', 'probe', 'r2l', 'u2r']
        weights = [0.6, 0.2, 0.1, 0.07, 0.03]
        df['label'] = np.random.choice(attack_types, n_samples, p=weights)
        
        return df
        
    def create_synthetic_labels(self, X):
        """Create synthetic labels based on feature patterns"""
        # Simple heuristic to create labels
        labels = []
        for _, row in X.iterrows():
            # Create some rules for different attack types
            if row.sum() > X.mean().sum() * 2:
                labels.append('dos')
            elif row.std() > X.std().mean() * 1.5:
                labels.append('probe')
            elif any(row > X.quantile(0.95)):
                labels.append('r2l')
            elif any(row < X.quantile(0.05)):
                labels.append('u2r')
            else:
                labels.append('normal')
        return labels
        
    def train_models(self, X, y):
        """Train multiple ML models"""
        models = {
            'random_forest': RandomForestClassifier(n_estimators=100, random_state=42),
            'neural_network': MLPClassifier(hidden_layer_sizes=(100, 50), max_iter=500, random_state=42),
            'svm': SVC(kernel='rbf', random_state=42),
            'decision_tree': DecisionTreeClassifier(random_state=42),
            'naive_bayes': GaussianNB()
        }
        
        # Split data
        X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
        
        results = {}
        
        for name, model in models.items():
            print(f"\nTraining {name}...")
            
            # Train model
            model.fit(X_train, y_train)
            
            # Make predictions
            y_pred = model.predict(X_test)
            
            # Calculate metrics
            accuracy = accuracy_score(y_test, y_pred)
            
            print(f"{name} accuracy: {accuracy:.4f}")
            
            # Save model
            model_path = os.path.join(self.model_output_dir, f"{name}_ids.pkl")
            joblib.dump(model, model_path)
            
            # Save scaler and label encoder with the first model
            if name == 'random_forest':
                scaler_path = os.path.join(self.model_output_dir, "scaler.pkl")
                encoder_path = os.path.join(self.model_output_dir, "label_encoder.pkl")
                joblib.dump(self.scaler, scaler_path)
                joblib.dump(self.label_encoder, encoder_path)
            
            results[name] = {
                'accuracy': accuracy,
                'model_path': model_path,
                'trained_at': datetime.now().isoformat()
            }
            
        return results
        
    def save_training_report(self, results, classes):
        """Save training report"""
        report = {
            'training_date': datetime.now().isoformat(),
            'models': results,
            'classes': classes,
            'data_path': self.data_path,
            'model_output_dir': self.model_output_dir
        }
        
        report_path = os.path.join(self.model_output_dir, "training_report.json")
        with open(report_path, 'w') as f:
            json.dump(report, f, indent=2)
            
        print(f"\nTraining report saved to: {report_path}")
        return report_path

def main():
    if len(sys.argv) < 2:
        print("Usage: python train_model.py <data_path> [model_output_dir]")
        print("Example: python train_model.py data/network_traffic.csv models/")
        sys.exit(1)
        
    data_path = sys.argv[1]
    model_output_dir = sys.argv[2] if len(sys.argv) > 2 else "../storage/app/models"
    
    trainer = IDSModelTrainer(data_path, model_output_dir)
    
    # Load and preprocess data
    X, y, classes = trainer.load_and_preprocess_data()
    
    # Train models
    results = trainer.train_models(X, y)
    
    # Save report
    trainer.save_training_report(results, classes)
    
    print("\nTraining completed successfully!")
    print(f"Models saved to: {model_output_dir}")

if __name__ == "__main__":
    main()

