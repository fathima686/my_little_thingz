<?php

/**
 * Backpropagation Neural Network (BPNN) for Gift Preference Prediction
 * 
 * This class implements a multi-layer perceptron with backpropagation
 * for predicting customer gift preferences based on user behavior data.
 */
class BPNNNeuralNetwork
{
    private $inputSize;
    private $hiddenLayers;
    private $outputSize;
    private $weights;
    private $biases;
    private $learningRate;
    private $activationFunction;
    private $isTrained = false;

    /**
     * Constructor
     * 
     * @param int $inputSize Number of input features
     * @param array $hiddenLayers Array of hidden layer sizes
     * @param int $outputSize Number of output neurons
     * @param float $learningRate Learning rate for training
     * @param string $activationFunction Activation function ('sigmoid', 'tanh', 'relu')
     */
    public function __construct($inputSize, $hiddenLayers, $outputSize = 1, $learningRate = 0.01, $activationFunction = 'sigmoid')
    {
        $this->inputSize = $inputSize;
        $this->hiddenLayers = $hiddenLayers;
        $this->outputSize = $outputSize;
        $this->learningRate = $learningRate;
        $this->activationFunction = $activationFunction;
        
        $this->initializeWeights();
    }

    /**
     * Initialize weights and biases randomly
     */
    private function initializeWeights()
    {
        $this->weights = [];
        $this->biases = [];
        
        $layers = array_merge([$this->inputSize], $this->hiddenLayers, [$this->outputSize]);
        
        for ($i = 0; $i < count($layers) - 1; $i++) {
            $rows = $layers[$i + 1];
            $cols = $layers[$i];
            
            // Xavier initialization
            $limit = sqrt(6.0 / ($rows + $cols));
            
            $this->weights[$i] = [];
            $this->biases[$i] = [];
            
            for ($j = 0; $j < $rows; $j++) {
                $this->weights[$i][$j] = [];
                for ($k = 0; $k < $cols; $k++) {
                    $this->weights[$i][$j][$k] = (mt_rand() / mt_getrandmax()) * 2 * $limit - $limit;
                }
                $this->biases[$i][$j] = (mt_rand() / mt_getrandmax()) * 2 * $limit - $limit;
            }
        }
    }

    /**
     * Apply activation function
     * 
     * @param float $x Input value
     * @return float Activated value
     */
    private function activate($x)
    {
        switch ($this->activationFunction) {
            case 'sigmoid':
                return 1 / (1 + exp(-$x));
            case 'tanh':
                return tanh($x);
            case 'relu':
                return max(0, $x);
            default:
                return 1 / (1 + exp(-$x));
        }
    }

    /**
     * Apply derivative of activation function
     * 
     * @param float $x Input value
     * @return float Derivative value
     */
    private function activateDerivative($x)
    {
        switch ($this->activationFunction) {
            case 'sigmoid':
                $sigmoid = $this->activate($x);
                return $sigmoid * (1 - $sigmoid);
            case 'tanh':
                $tanh = tanh($x);
                return 1 - $tanh * $tanh;
            case 'relu':
                return $x > 0 ? 1 : 0;
            default:
                $sigmoid = $this->activate($x);
                return $sigmoid * (1 - $sigmoid);
        }
    }

    /**
     * Forward propagation
     * 
     * @param array $inputs Input features
     * @return array Network outputs and intermediate values
     */
    public function forward($inputs)
    {
        $layers = array_merge([$this->inputSize], $this->hiddenLayers, [$this->outputSize]);
        $activations = [$inputs];
        $zValues = [];
        
        for ($i = 0; $i < count($layers) - 1; $i++) {
            $z = [];
            $a = [];
            
            for ($j = 0; $j < $layers[$i + 1]; $j++) {
                $sum = $this->biases[$i][$j];
                for ($k = 0; $k < $layers[$i]; $k++) {
                    $sum += $this->weights[$i][$j][$k] * $activations[$i][$k];
                }
                $z[$j] = $sum;
                $a[$j] = $this->activate($sum);
            }
            
            $zValues[] = $z;
            $activations[] = $a;
        }
        
        return [
            'activations' => $activations,
            'zValues' => $zValues
        ];
    }

    /**
     * Backward propagation
     * 
     * @param array $inputs Input features
     * @param array $targets Target values
     * @param array $forwardResult Result from forward propagation
     * @return array Weight and bias gradients
     */
    private function backward($inputs, $targets, $forwardResult)
    {
        $activations = $forwardResult['activations'];
        $zValues = $forwardResult['zValues'];
        
        $layers = array_merge([$this->inputSize], $this->hiddenLayers, [$this->outputSize]);
        $numLayers = count($layers) - 1;
        
        // Calculate output layer error
        $outputLayer = $numLayers - 1;
        $outputError = [];
        for ($i = 0; $i < $this->outputSize; $i++) {
            $outputError[$i] = ($activations[$numLayers][$i] - $targets[$i]) * 
                              $this->activateDerivative($zValues[$outputLayer][$i]);
        }
        
        $errors = [$outputError];
        
        // Backpropagate errors through hidden layers
        for ($layer = $numLayers - 2; $layer >= 0; $layer--) {
            $layerError = [];
            for ($i = 0; $i < $layers[$layer + 1]; $i++) {
                $error = 0;
                for ($j = 0; $j < $layers[$layer + 2]; $j++) {
                    $error += $errors[0][$j] * $this->weights[$layer + 1][$j][$i];
                }
                $layerError[$i] = $error * $this->activateDerivative($zValues[$layer][$i]);
            }
            array_unshift($errors, $layerError);
        }
        
        // Calculate gradients
        $weightGradients = [];
        $biasGradients = [];
        
        for ($layer = 0; $layer < $numLayers; $layer++) {
            $weightGradients[$layer] = [];
            $biasGradients[$layer] = [];
            
            for ($j = 0; $j < $layers[$layer + 1]; $j++) {
                $biasGradients[$layer][$j] = $errors[$layer][$j];
                $weightGradients[$layer][$j] = [];
                
                for ($k = 0; $k < $layers[$layer]; $k++) {
                    $weightGradients[$layer][$j][$k] = $errors[$layer][$j] * $activations[$layer][$k];
                }
            }
        }
        
        return [
            'weightGradients' => $weightGradients,
            'biasGradients' => $biasGradients
        ];
    }

    /**
     * Update weights and biases using gradients
     * 
     * @param array $gradients Gradients from backward propagation
     */
    private function updateWeights($gradients)
    {
        $layers = array_merge([$this->inputSize], $this->hiddenLayers, [$this->outputSize]);
        $numLayers = count($layers) - 1;
        
        for ($layer = 0; $layer < $numLayers; $layer++) {
            for ($j = 0; $j < $layers[$layer + 1]; $j++) {
                $this->biases[$layer][$j] -= $this->learningRate * $gradients['biasGradients'][$layer][$j];
                
                for ($k = 0; $k < $layers[$layer]; $k++) {
                    $this->weights[$layer][$j][$k] -= $this->learningRate * $gradients['weightGradients'][$layer][$j][$k];
                }
            }
        }
    }

    /**
     * Train the neural network
     * 
     * @param array $trainingData Array of training samples [inputs, targets]
     * @param int $epochs Number of training epochs
     * @param float $validationSplit Fraction of data to use for validation
     * @return array Training history
     */
    public function train($trainingData, $epochs = 1000, $validationSplit = 0.2)
    {
        $history = [
            'training_loss' => [],
            'validation_loss' => [],
            'training_accuracy' => [],
            'validation_accuracy' => []
        ];
        
        // Split data into training and validation sets
        $validationSize = (int)(count($trainingData) * $validationSplit);
        $validationData = array_slice($trainingData, 0, $validationSize);
        $trainData = array_slice($trainingData, $validationSize);
        
        for ($epoch = 0; $epoch < $epochs; $epoch++) {
            // Shuffle training data
            shuffle($trainData);
            
            $trainingLoss = 0;
            $trainingAccuracy = 0;
            
            // Train on each sample
            foreach ($trainData as $sample) {
                $inputs = $sample[0];
                $targets = $sample[1];
                
                // Forward propagation
                $forwardResult = $this->forward($inputs);
                $outputs = $forwardResult['activations'][count($forwardResult['activations']) - 1];
                
                // Calculate loss (Mean Squared Error)
                $loss = 0;
                for ($i = 0; $i < count($outputs); $i++) {
                    $loss += pow($outputs[$i] - $targets[$i], 2);
                }
                $trainingLoss += $loss / count($outputs);
                
                // Calculate accuracy (for binary classification)
                $predicted = $outputs[0] > 0.5 ? 1 : 0;
                $actual = $targets[0] > 0.5 ? 1 : 0;
                if ($predicted == $actual) {
                    $trainingAccuracy += 1;
                }
                
                // Backward propagation and weight update
                $gradients = $this->backward($inputs, $targets, $forwardResult);
                $this->updateWeights($gradients);
            }
            
            $trainingLoss /= count($trainData);
            $trainingAccuracy /= count($trainData);
            
            // Validation
            $validationLoss = 0;
            $validationAccuracy = 0;
            
            foreach ($validationData as $sample) {
                $inputs = $sample[0];
                $targets = $sample[1];
                
                $forwardResult = $this->forward($inputs);
                $outputs = $forwardResult['activations'][count($forwardResult['activations']) - 1];
                
                $loss = 0;
                for ($i = 0; $i < count($outputs); $i++) {
                    $loss += pow($outputs[$i] - $targets[$i], 2);
                }
                $validationLoss += $loss / count($outputs);
                
                $predicted = $outputs[0] > 0.5 ? 1 : 0;
                $actual = $targets[0] > 0.5 ? 1 : 0;
                if ($predicted == $actual) {
                    $validationAccuracy += 1;
                }
            }
            
            $validationLoss /= count($validationData);
            $validationAccuracy /= count($validationData);
            
            $history['training_loss'][] = $trainingLoss;
            $history['validation_loss'][] = $validationLoss;
            $history['training_accuracy'][] = $trainingAccuracy;
            $history['validation_accuracy'][] = $validationAccuracy;
            
            // Early stopping if validation loss starts increasing
            if ($epoch > 50 && $validationLoss > $history['validation_loss'][$epoch - 10]) {
                break;
            }
        }
        
        $this->isTrained = true;
        return $history;
    }

    /**
     * Make a prediction
     * 
     * @param array $inputs Input features
     * @return array Prediction results
     */
    public function predict($inputs)
    {
        if (!$this->isTrained) {
            throw new Exception('Network must be trained before making predictions');
        }
        
        $forwardResult = $this->forward($inputs);
        $outputs = $forwardResult['activations'][count($forwardResult['activations']) - 1];
        
        return [
            'prediction' => $outputs[0],
            'confidence' => abs($outputs[0] - 0.5) * 2, // Confidence based on distance from 0.5
            'raw_output' => $outputs
        ];
    }

    /**
     * Get network weights and biases for serialization
     * 
     * @return array Network parameters
     */
    public function getParameters()
    {
        return [
            'weights' => $this->weights,
            'biases' => $this->biases,
            'inputSize' => $this->inputSize,
            'hiddenLayers' => $this->hiddenLayers,
            'outputSize' => $this->outputSize,
            'learningRate' => $this->learningRate,
            'activationFunction' => $this->activationFunction,
            'isTrained' => $this->isTrained
        ];
    }

    /**
     * Load network parameters from serialized data
     * 
     * @param array $parameters Serialized network parameters
     */
    public function loadParameters($parameters)
    {
        $this->weights = $parameters['weights'];
        $this->biases = $parameters['biases'];
        $this->inputSize = $parameters['inputSize'];
        $this->hiddenLayers = $parameters['hiddenLayers'];
        $this->outputSize = $parameters['outputSize'];
        $this->learningRate = $parameters['learningRate'];
        $this->activationFunction = $parameters['activationFunction'];
        $this->isTrained = $parameters['isTrained'];
    }

    /**
     * Normalize input features to 0-1 range
     * 
     * @param array $inputs Raw input features
     * @param array $minValues Minimum values for each feature
     * @param array $maxValues Maximum values for each feature
     * @return array Normalized features
     */
    public static function normalizeInputs($inputs, $minValues, $maxValues)
    {
        $normalized = [];
        for ($i = 0; $i < count($inputs); $i++) {
            $range = $maxValues[$i] - $minValues[$i];
            if ($range > 0) {
                $normalized[$i] = ($inputs[$i] - $minValues[$i]) / $range;
            } else {
                $normalized[$i] = 0.5; // Default middle value
            }
        }
        return $normalized;
    }

    /**
     * Denormalize output prediction to original scale
     * 
     * @param float $prediction Normalized prediction (0-1)
     * @param float $minValue Minimum value in original scale
     * @param float $maxValue Maximum value in original scale
     * @return float Denormalized prediction
     */
    public static function denormalizeOutput($prediction, $minValue = 0, $maxValue = 1)
    {
        return $minValue + $prediction * ($maxValue - $minValue);
    }
}






















