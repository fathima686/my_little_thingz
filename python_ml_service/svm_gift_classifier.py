"""
SVM Gift Classifier - Classify gifts as Trending or Normal
Uses Support Vector Machine (SVM) with features:
- recent_sales_count
- total_views
- average_rating
- number_of_reviews
"""

import numpy as np
import pandas as pd
from sklearn.svm import SVC
from sklearn.preprocessing import StandardScaler
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix

class GiftTrendingClassifier:
    def __init__(self, kernel='rbf', C=1.0):
        """
        Initialize the SVM classifier
        Args:
            kernel: 'linear', 'rbf', 'poly', 'sigmoid'
            C: Regularization parameter
        """
        self.model = SVC(kernel=kernel, C=C, random_state=42)
        self.scaler = StandardScaler()
        self.is_trained = False
        
    def create_sample_dataset(self):
        """
        Create a sample dataset of gifts with features
        Returns: DataFrame with features and target
        """
        np.random.seed(42)
        n_samples = 200
        
        data = []
        
        for i in range(n_samples):
            # Trending gifts (target='Trending')
            trending = np.random.choice([True, False])
            
            if trending:
                # Trending gifts have higher sales, views, and ratings
                recent_sales = np.random.randint(50, 200)
                total_views = np.random.randint(500, 3000)
                avg_rating = np.random.uniform(4.0, 5.0)
                num_reviews = np.random.randint(20, 100)
                status = 'Trending'
            else:
                # Normal gifts have lower activity
                recent_sales = np.random.randint(0, 50)
                total_views = np.random.randint(50, 500)
                avg_rating = np.random.uniform(3.0, 4.5)
                num_reviews = np.random.randint(5, 30)
                status = 'Normal'
            
            data.append({
                'recent_sales_count': recent_sales,
                'total_views': total_views,
                'average_rating': avg_rating,
                'number_of_reviews': num_reviews,
                'status': status
            })
        
        return pd.DataFrame(data)
    
    def train(self, X, y):
        """
        Train the SVM model
        Args:
            X: Features
            y: Target labels
        """
        print("Training SVM Model...")
        print(f"Kernel: {self.model.kernel}, C: {self.model.C}")
        print(f"Features: {X.shape[1]}, Samples: {X.shape[0]}")
        
        # Scale features
        X_scaled = self.scaler.fit_transform(X)
        
        # Train model
        self.model.fit(X_scaled, y)
        self.is_trained = True
        
        print("Model trained successfully!")
    
    def predict(self, X):
        """
        Predict status for gifts
        Args:
            X: Features (array or DataFrame)
        Returns:
            predictions, prediction_proba
        """
        if not self.is_trained:
            raise ValueError("Model must be trained before making predictions")
        
        # Scale features
        if isinstance(X, pd.DataFrame):
            X_array = X.values
        else:
            X_array = X
            
        X_scaled = self.scaler.transform(X_array)
        
        # Make predictions
        predictions = self.model.predict(X_scaled)
        
        return predictions
    
    def evaluate(self, X_test, y_test):
        """
        Evaluate the model and print metrics
        Args:
            X_test: Test features
            y_test: Test labels
        """
        # Make predictions
        y_pred = self.predict(X_test)
        
        # Calculate accuracy
        accuracy = accuracy_score(y_test, y_pred)
        
        print(f"\n{'='*70}")
        print("MODEL EVALUATION")
        print(f"{'='*70}")
        print(f"\nAccuracy: {accuracy:.2%}")
        
        # Confusion matrix
        print(f"\nConfusion Matrix:")
        print(f"{'='*70}")
        cm = confusion_matrix(y_test, y_pred, labels=self.model.classes_)
        
        print(f"{' '*25}", end="")
        for cls in self.model.classes_:
            print(f"{cls:^15}", end="")
        print()
        
        for i, true_label in enumerate(self.model.classes_):
            print(f"{true_label:^25}", end="")
            for j in range(len(self.model.classes_)):
                print(f"{cm[i][j]:^15}", end="")
            print()
        
        # Classification report
        print(f"\n{'='*70}")
        print("Classification Report:")
        print(f"{'='*70}")
        print(classification_report(y_test, y_pred))
        
        return accuracy, cm


def main():
    """Main function to demonstrate the SVM gift classifier"""
    print("="*70)
    print("SVM Gift Trending Classifier")
    print("Classifies gifts as Trending or Normal based on sales metrics")
    print("="*70)
    
    # Initialize classifier
    classifier = GiftTrendingClassifier(kernel='rbf', C=1.0)
    
    # Create sample dataset
    print("\nCreating sample dataset...")
    df = classifier.create_sample_dataset()
    
    print(f"\nDataset created: {len(df)} gifts")
    print(f"Trending gifts: {len(df[df['status'] == 'Trending'])}")
    print(f"Normal gifts: {len(df[df['status'] == 'Normal'])}")
    
    print(f"\nSample data:")
    print(df.head(10))
    
    # Prepare features and target
    feature_columns = ['recent_sales_count', 'total_views', 'average_rating', 'number_of_reviews']
    X = df[feature_columns]
    y = df['status']
    
    # Split data
    print(f"\n{'='*70}")
    print("SPLITTING DATA")
    print(f"{'='*70}")
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y
    )
    
    print(f"Training samples: {len(X_train)}")
    print(f"Testing samples: {len(X_test)}")
    
    # Train model
    classifier.train(X_train, y_train)
    
    # Evaluate model
    classifier.evaluate(X_test, y_test)
    
    # Manual predictions
    print(f"\n{'='*70}")
    print("MANUAL PREDICTIONS")
    print(f"{'='*70}")
    
    test_gifts = [
        {
            'name': 'Popular Wedding Gift Box',
            'recent_sales_count': 150,
            'total_views': 2500,
            'average_rating': 4.8,
            'number_of_reviews': 85
        },
        {
            'name': 'Basic Photo Frame',
            'recent_sales_count': 25,
            'total_views': 300,
            'average_rating': 3.5,
            'number_of_reviews': 12
        },
        {
            'name': 'Custom Chocolate Box',
            'recent_sales_count': 120,
            'total_views': 1800,
            'average_rating': 4.7,
            'number_of_reviews': 65
        },
        {
            'name': 'Simple Greeting Card',
            'recent_sales_count': 15,
            'total_views': 150,
            'average_rating': 3.2,
            'number_of_reviews': 8
        }
    ]
    
    print("\nPredicting status for sample gifts:")
    print("-"*70)
    
    for gift in test_gifts:
        features = np.array([[
            gift['recent_sales_count'],
            gift['total_views'],
            gift['average_rating'],
            gift['number_of_reviews']
        ]])
        
        prediction = classifier.predict(features)[0]
        
        print(f"\nGift: {gift['name']}")
        print(f"  Recent Sales: {gift['recent_sales_count']}")
        print(f"  Total Views: {gift['total_views']}")
        print(f"  Avg Rating: {gift['average_rating']}")
        print(f"  Num Reviews: {gift['number_of_reviews']}")
        print(f"  Status: {'✓ TRENDING ⬆️' if prediction == 'Trending' else '○ NORMAL'}")
    
    # Interactive mode
    print(f"\n{'='*70}")
    print("INTERACTIVE MODE")
    print(f"{'='*70}")
    print("\nEnter gift details to predict status (or 'quit' to exit):")
    
    while True:
        try:
            print("\nNew Gift Details:")
            recent_sales = int(input("Recent sales count: "))
            total_views = int(input("Total views: "))
            avg_rating = float(input("Average rating (0-5): "))
            num_reviews = int(input("Number of reviews: "))
            
            features = np.array([[
                recent_sales,
                total_views,
                avg_rating,
                num_reviews
            ]])
            
            prediction = classifier.predict(features)[0]
            
            print(f"\n{'='*70}")
            print(f"PREDICTION: {'✓ TRENDING ⬆️' if prediction == 'Trending' else '○ NORMAL'}")
            print(f"{'='*70}")
            
            # Analyze why
            if prediction == 'Trending':
                print("\nThis gift is classified as TRENDING because:")
                if recent_sales >= 50:
                    print(f"  • High recent sales ({recent_sales})")
                if total_views >= 500:
                    print(f"  • High total views ({total_views})")
                if avg_rating >= 4.0:
                    print(f"  • Excellent rating ({avg_rating})")
                if num_reviews >= 20:
                    print(f"  • Many reviews ({num_reviews})")
            
            continue_prompt = input("\nPredict another gift? (yes/no): ").lower()
            if continue_prompt != 'yes':
                break
                
        except ValueError:
            print("Invalid input. Please enter numbers.")
        except KeyboardInterrupt:
            print("\n\nExiting...")
            break
        except Exception as e:
            print(f"Error: {e}")
    
    print("\n" + "="*70)
    print("Program Complete!")
    print("="*70)


if __name__ == "__main__":
    main()




















