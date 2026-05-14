import React from 'react';
import Barcode from 'react-barcode';

interface BarcodeProps {
  value: string;
  width?: number;
  height?: number;
  displayValue?: boolean;
  format?: 'CODE128' | 'CODE39' | 'EAN13' | 'EAN8' | 'UPC' | 'ITF14';
  fontSize?: number;
  margin?: number;
  background?: string;
  lineColor?: string;
}

export const ProductBarcode: React.FC<BarcodeProps> = ({
  value,
  width = 1.4,
  height = 22,
  displayValue = true,
  format = 'CODE128',
  fontSize = 12,
  margin = 5,
  background = '#ffffff',
  lineColor = '#000000',
}) => {
  if (!value) {
    return null;
  }

  return (
    <div className="barcode-container inline-block">
      <Barcode
        value={value}
        format={format}
        width={width}
        height={height}
        displayValue={displayValue}
        fontSize={fontSize}
        margin={margin}
        background={background}
        lineColor={lineColor}
      />
    </div>
  );
};
