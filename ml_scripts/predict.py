#!/usr/bin/env python3
"""
AI-Powered IDS - Machine Learning Prediction Script
This script performs intrusion detection on network traffic data.
"""

import pandas as pd
import numpy as np
import joblib
import os
import sys
import json
from datetime import datetime

class IDSPredictor:
    def __init__(self, model_dir):
        self.model_dir = model_dir
        self.models = {}
        self.scaler = None
        self.label_encoder = None
        self.load_models()
        
    def load_models(self):
        """Load trained models and preprocessors"""
        try:
            # Load scaler and label encoder
            scaler_path = os.path.join(self.model_dir, "scaler.pkl")
            encoder_path = os.path.join(self.model_dir, "label_encoder.pkl")
            
            if os.path.exists(scaler_path):
                self.scaler = joblib.load(scaler_path)
            if os.path.exists(encoder_path):
                self.label_encoder = joblib.load(encoder_path)
                
            # Load models
            model_files = [f for f in os.listdir(self.model_dir) if f.endswith('_ids.pkl')]
            
            for model_file in model_files:
                model_name = model_file.replace('_ids.pkl', '')
                model_path = os.path.join(self.model_dir, model_file)
                self.models[model_name] = joblib.load(model_path)
                
            print(f"Loaded {len(self.models)} models: {list(self.models.keys())}")
            
        except Exception as e:
            print(f"Error loading models: {e}")
            
    def preprocess_data(self, data_path):
        """Preprocess input data for prediction"""
        try:
            # Load data
            if data_path.endswith('.csv'):
                df = pd.read_csv(data_path)
            else:
                # Handle other formats or create sample data
                df = self.create_sample_test_data()
                
            # Remove target columns if present
            target_cols = ['label', 'attack_type', 'class']
            for col in target_cols:
                if col in df.columns:
                    df = df.drop(col, axis=1)
                    
            # Handle categorical features
            for col in df.select_dtypes(include=['object']).columns:
                # Simple encoding for categorical features
                df[col] = pd.Categorical(df[col]).codes
                
            # Scale features if scaler is available
            if self.scaler is not None:
                # Ensure we have the same number of features as training
                try:
                    X_scaled = self.scaler.transform(df)
                except ValueError as e:
                    print(f"Feature mismatch: {e}")
                    # Create dummy features to match training data
                    X_scaled = self.create_dummy_features(df)
            else:
                X_scaled = df.values
                
            return X_scaled, df
            
        except Exception as e:
            print(f"Error preprocessing data: {e}")
            return None, None
            
    def create_sample_test_data(self):
        """Create sample test data for demonstration"""
        np.random.seed(123)
        n_samples = 100
        
        # Generate synthetic network features (same structure as training)
        data = {
            'duration': np.random.exponential(2, n_samples),
            'protocol_type': np.random.choice([0, 1, 2], n_samples),  # Already encoded
            'service': np.random.choice([0, 1, 2, 3, 4], n_samples),
            'flag': np.random.choice([0, 1, 2, 3], n_samples),
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
        
        return pd.DataFrame(data)
        
    def create_dummy_features(self, df):
        """Create dummy features to match training data dimensions"""
        # This is a fallback method when feature dimensions don't match
        n_features = 38  # Expected number of features from training
        n_samples = len(df)
        
        # Use available features and pad with zeros if needed
        available_features = min(df.shape[1], n_features)
        X = np.zeros((n_samples, n_features))
        X[:, :available_features] = df.iloc[:, :available_features].values
        
        return X
        
    def predict(self, data_path, model_name='random_forest'):
        """Make predictions on network traffic data"""
        if model_name not in self.models:
            print(f"Model {model_name} not found. Available models: {list(self.models.keys())}")
            return None
            
        # Preprocess data
        X, original_df = self.preprocess_data(data_path)
        if X is None:
            return None
            
        # Make predictions
        model = self.models[model_name]
        predictions = model.predict(X)
        
        # Get prediction probabilities if available
        try:
            probabilities = model.predict_proba(X)
            max_probs = np.max(probabilities, axis=1)
        except:
            max_probs = np.ones(len(predictions))  # Default confidence
            
        # Convert predictions back to labels
        if self.label_encoder is not None:
            predicted_labels = self.label_encoder.inverse_transform(predictions)
        else:
            predicted_labels = predictions
            
        # Create results
        results = []
        for i, (pred_label, confidence) in enumerate(zip(predicted_labels, max_probs)):
            results.append({
                'index': i,
                'prediction': pred_label,
                'confidence': float(confidence),
                'is_attack': pred_label != 'normal',
                'severity': self.get_severity(pred_label, confidence)
            })
            
        return results
        
    def get_severity(self, attack_type, confidence):
        """Determine severity based on attack type and confidence"""
        if attack_type == 'normal':
            return 'low'
            
        severity_map = {
            'dos': 'high',
            'ddos': 'critical',
            'probe': 'medium',
            'r2l': 'high',
            'u2r': 'critical',
            'brute_force': 'high',
            'sql_injection': 'critical',
            'xss': 'medium',
            'port_scan': 'medium',
            'malware': 'critical',
            'phishing': 'high'
        }
        
        base_severity = severity_map.get(attack_type.lower(), 'medium')
        
        # Adjust based on confidence
        if confidence > 0.9:
            if base_severity == 'medium':
                return 'high'
            elif base_severity == 'high':
                return 'critical'
        elif confidence < 0.6:
            if base_severity == 'critical':
                return 'high'
            elif base_severity == 'high':
                return 'medium'
                
        return base_severity
        
    def batch_predict(self, data_path, output_path=None):
        """Perform batch prediction and save results"""
        all_results = {}
        
        for model_name in self.models.keys():
            print(f"\nRunning predictions with {model_name}...")
            results = self.predict(data_path, model_name)
            
            if results:
                all_results[model_name] = results
                
                # Count attack types
                attack_counts = {}
                for result in results:
                    attack_type = result['prediction']
                    attack_counts[attack_type] = attack_counts.get(attack_type, 0) + 1
                    
                print(f"Attack type distribution: {attack_counts}")
                
        # Save results if output path provided
        if output_path and all_results:
            report = {
                'prediction_date': datetime.now().isoformat(),
                'data_path': data_path,
                'results': all_results,
                'summary': self.generate_summary(all_results)
            }
            
            with open(output_path, 'w') as f:
                json.dump(report, f, indent=2)
                
            print(f"\nResults saved to: {output_path}")
            
        return all_results
        
    def generate_summary(self, all_results):
        """Generate summary statistics from prediction results"""
        summary = {}
        
        for model_name, results in all_results.items():
            total_samples = len(results)
            attack_samples = sum(1 for r in results if r['is_attack'])
            
            # Count by severity
            severity_counts = {}
            for result in results:
                severity = result['severity']
                severity_counts[severity] = severity_counts.get(severity, 0) + 1
                
            summary[model_name] = {
                'total_samples': total_samples,
                'attack_samples': attack_samples,
                'normal_samples': total_samples - attack_samples,
                'attack_rate': attack_samples / total_samples if total_samples > 0 else 0,
                'severity_distribution': severity_counts
            }
            
        return summary

def main():
    if len(sys.argv) < 3:
        print("Usage: python predict.py <model_dir> <data_path> [output_path]")
        print("Example: python predict.py ../storage/app/models data/test_traffic.csv results.json")
        sys.exit(1)
        
    model_dir = sys.argv[1]
    data_path = sys.argv[2]
    output_path = sys.argv[3] if len(sys.argv) > 3 else None
    
    predictor = IDSPredictor(model_dir)
    
    if not predictor.models:
        print("No models found. Please train models first.")
        sys.exit(1)
        
    # Perform batch prediction
    results = predictor.batch_predict(data_path, output_path)
    
    if results:
        print("\nPrediction completed successfully!")
    else:
        print("Prediction failed!")

if __name__ == "__main__":
    main()

