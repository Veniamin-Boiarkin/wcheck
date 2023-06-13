<?php

class Wallet {

    // Wallet filepath
    private $path;

    // Wallet lock filepath
    private $pathLock;

    // The list of coins
    private $coins = [];

    // Sum of coins
    private $sum = 0;

    /**
     * @throws Exception
     */
    public function __construct(string $path) {
        $path = trim($path);
        if ($path === '') {
            throw new Exception('Incorrect wallet filepath');
        }
        if (!file_exists($path)) {
            throw new Exception('Wallet filepath does not exist');
        }
        $this->path = $path;
        $this->pathLock = $path.'.lock';
        if ($this->isLocked()) {
            throw new Exception('Wallet ('.$path.') is locked');
        }
        $this->lock();

        $content = trim(file_get_contents($this->path));
        if ($content !== '') {
            $coins = explode(',', $content);
            foreach ($coins as $coin) {
                $coin = (int)$coin;
                if ($coin > 0) {
                    $this->coins[] = $coin;
                }
            }
            $this->sum = $this->getSumOfCoins();
        }
    }

    /**
     * Checks whether a wallet is locked
     * @return bool
     */
    private function isLocked(): bool {
        return file_exists($this->pathLock);
    }

    /**
     * Lock a wallet | Create a lock file
     * @return void
     */
    private function lock() {
        if ($this->isLocked()) {
            return;
        }
        file_put_contents($this->pathLock, '');
    }

    /**
     * Unlock a wallet | Delete lock file
     * @return void
     */
    public function saveAndUnlock() {
        file_put_contents($this->path, implode(',', $this->coins));
        unlink($this->pathLock);
    }

    /**
     * Print the list of coins and total amount
     * @return void
     */
    public function printInfo() {
        echo '----- Wallet Info -----'.PHP_EOL;
        echo 'Coins: '.implode(', ', $this->coins).PHP_EOL;
        echo 'Sum of coins: '.$this->sum.PHP_EOL;
        echo '-----------------------'.PHP_EOL;
    }

    /**
     * Calculates the sum of coins
     * @return int
     */
    public function getSumOfCoins(): int {
        return array_sum($this->coins);
    }

    /**
     * Add a new coin
     * @throws Exception
     */
    public function addCoin(int $amount): void {
        if ($amount <= 0) {
            throw new Exception('Incorrect amount ('.$amount.') to add');
        }
        $this->coins[] = $amount;
        $this->sum = $this->getSumOfCoins();
    }

    /**
     * Spend coins
     * @param int $amount
     * @return void
     * @throws Exception
     */
    public function spendCoins(int $amount): void {
        if ($amount <= 0) {
            throw new Exception('Incorrect amount ('.$amount.') to spend');
        }
        if ($amount > $this->sum) {
            throw new Exception('Not enough money to spend. Got '.$this->sum.', but want '.$amount);
        }
        $coinIndex = array_search($amount, $this->coins);
        if ($coinIndex !== false) {
            unset($this->coins[ $coinIndex ]);
        } else {
            rsort($this->coins);
            $left = $amount;
            foreach ($this->coins as $index => $coinValue) {
                if ($left >= $coinValue) {
                    $left -= $coinValue;
                } else {
                    $this->addCoin($coinValue - $left);
                    $left = 0;
                }
                unset($this->coins[ $index ]);
                if ($left === 0) {
                    break;
                }
            }
        }
        $this->sum = $this->getSumOfCoins();
    }
}

$wallet = null;
try {
    $wallet = new Wallet('./wallet.data');
} catch (Exception $ex) {
    echo 'Error: '.$ex->getMessage().PHP_EOL;
    exit;
}

$wallet->printInfo();
try {
    $wallet->spendCoins(7);
} catch (Exception $ex) {
    echo 'Error: '.$ex->getMessage().PHP_EOL;
}
$wallet->printInfo();
$wallet->saveAndUnlock();