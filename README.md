# Interfacing a HD44780 LCD with Raspberry Pi over I2C

More info in my blog here: https://ptodorov.com/interfacing-a-hd44780-lcd-with-raspberry-pi-over-i2c/

![My LCD](https://ptodorov.com/wp-content/uploads/2019/12/sunoo_lcd_test.jpg)

Aliexpress LCD and I2C backpack link: https://www.aliexpress.com/item/1709948156.html

### Connecting the I2C backpack to your Raspberry:
| I2C Backpack | Raspberry Pi 3+ |
| --- | --- |
| SCL | Pin 05 (SCL1) |
| SDA | Pin 03 (SDA1) |
| VCC | Pin 04 (+5V) |
| GND | Pin 06 (GND) |

### Upgrade all apps and libraries:
```sudo apt-get update && sudo apt-get upgrade```

### Install prerequisites:
```sudo apt-get install i2c-tools```
```sudo apt-get install python-smbus```
```sudo apt-get install lcdproc```
  > select “Yes” when asked to perform an automatic configuration upgrade

### Enable Raspberry I2C interface:
```sudo raspi-config```
   > 5 Interfacing Options > P5 I2C > Yes

```sudo reboot```

### Find out the address of your I2C LCD
```sudo i2cdetect -y 1```
  > Write down your address, mine was 3f, you will need to set it in /etc/LCDd.conf later

### Download hd44780.so LCDProc driver with:
```wget https://github.com/wilberforce/lcdproc/blob/master/hd44780.so -O /usr/lib/lcdproc/hd44780.so```

### Clone this repository:
```cd /home/pi```
```git clone git@github.com:ptodorov0/lcdproc_hd44780_i2c.git```

### Editing LCDd.conf
Edit your /etc/LCDd.conf according to the LCDd.conf present in my repository, if you use the same display and convertor board you should just change the I2C address if it is not the same as mine.

### Enable LCDd on startup and start it
```sudo service LCDd enable```
```sudo service LCDd start```

### Test if LCD works with command:
```lcdproc```

**WARNING:** If you don't see anything on the LCD try to adjust the contrast potentiometer screw on the parallel to I2C convertor board.

### Adding the play.php script to cron
```crontab -e```
  > choose to edit it with your editor of choice and add the following line to the bottom of CRON table:
```* * * * * php /home/pi/lcdproc_hd44780_i2c/play.php >> /home/pi/lcdproc_hd44780_i2c/play_debug.log```

  > check if cron is currently started with the following command
```sudo service cron status```

  > if it is inactive (the default on Moode and maybe Raspbian), start cron service and enable it on startup
```sudo service cron enable```
```sudo service cron start```

